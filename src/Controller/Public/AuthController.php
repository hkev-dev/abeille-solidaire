<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\DTO\RegistrationDTO;
use Psr\Log\LoggerInterface;
use App\Form\RegistrationType;
use App\Service\ReferralService;
use App\Form\PaymentSelectionType;
use App\Repository\UserRepository;
use App\Exception\WebhookException;
use App\Service\StripeWebhookService;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RegistrationPaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SecurityService;  // Updated import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;

    public function __construct(
        private readonly UserRegistrationService $userRegistrationService,
        private readonly ReferralService $referralService,
        private readonly RegistrationPaymentService $registrationPaymentService,
        private readonly SecurityService $securityService,
        private readonly LoggerInterface $logger,
        UserRepository $userRepository,
    ) {
        $this->userRepository = $userRepository;
    }

    #[Route('/login', name: 'app.login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('landing.home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('public/pages/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'app.logout', methods: ['GET'])]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/register', name: 'app.register')]
    public function register(Request $request): Response
    {
        // Check rate limiting before processing
        $this->securityService->checkRegistrationThrottle();

        $dto = new RegistrationDTO();

        // Handle referral code
        if ($request->query->has('ref')) {
            $dto->referralCode = $request->query->get('ref');
        } elseif (in_array($this->getParameter('kernel.environment'), ['dev', 'test'])) {
            // In test/dev environment, use default referral code if none provided
            $rootUser = $this->userRepository->findOneBy(['email' => 'root@example.com']);
            if ($rootUser) {
                $dto->referralCode = 'ROOT_USER_REF';
            }
        }

        $form = $this->createForm(RegistrationType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify reCAPTCHA
            if (!$this->securityService->verifyRecaptcha($dto->recaptcha)) {
                $this->addFlash('error', 'Invalid reCAPTCHA. Please try again.');
                return $this->redirectToRoute('app.register');
            }

            try {
                // Validate referral code
                $referrer = $this->referralService->validateReferralCode($dto->referralCode);
                if (!$referrer) {
                    throw new \InvalidArgumentException('Invalid referral code.');
                }

                // Register user
                $user = $this->userRegistrationService->registerUser($dto, $referrer);

                $this->addFlash('success', 'Registration successful! Please complete your registration by making the initial donation.');
                return $this->redirectToRoute('app.registration.payment', ['id' => $user->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app.register');
            }
        }

        return $this->render('public/pages/auth/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/register/payment/{id}', name: 'app.registration.payment', methods: ['GET', 'POST'])]
    public function paymentSelection(User $user, Request $request): Response
    {
        if ($user->getRegistrationPaymentStatus() !== 'pending') {
            return $this->redirectToRoute('app.login');
        }

        // Handle AJAX requests for Stripe payment intent creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);

            if ($data['payment_method'] === 'stripe') {
                try {
                    $stripeData = $this->registrationPaymentService->createStripePaymentIntent($user);
                    // Store payment method in session
                    $request->getSession()->set('payment_method', 'stripe');
                    return $this->json([
                        'clientSecret' => $stripeData['clientSecret']
                    ]);
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        // Regular form handling
        $form = $this->createForm(PaymentSelectionType::class, null, [
            'action' => $this->generateUrl('app.registration.payment', ['id' => $user->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // This branch will handle non-AJAX form submissions if needed
            // Currently not used as both payment methods use AJAX
        }

        return $this->render('public/pages/auth/payment-selection.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/register/payment/{id}/crypto', name: 'app.registration.payment.crypto', methods: ['POST'])]
    public function cryptoPayment(User $user, Request $request): Response
    {
        if ($user->getRegistrationPaymentStatus() !== 'pending') {
            return $this->redirectToRoute('app.login');
        }

        $this->validateCsrfToken($request, 'crypto_payment');

        try {
            $transaction = $this->registrationPaymentService->createCoinPaymentsTransaction($user);

            // Store payment method in session before redirect
            $request->getSession()->set('payment_method', 'crypto');
            $request->getSession()->set('txn_id', $transaction['txn_id']);

            return $this->redirectToRoute('app.waiting_room', [
                'id' => $user->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            $this->addFlash('error', 'Failed to initialize cryptocurrency payment. Please try again.');
            return $this->redirectToRoute('app.registration.payment', ['id' => $user->getId()]);
        }
    }

    #[Route('/register/waiting-room/{id}', name: 'app.waiting_room')]
    public function waitingRoom(User $user, Request $request): Response
    {
        if ($user->getRegistrationPaymentStatus() === 'completed') {
            return $this->redirectToRoute('app.login');
        }

        if ($user->getRegistrationPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.registration.payment', ['id' => $user->getId()]);
        }

        // Get payment method from session
        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');

        return $this->render('public/pages/auth/waiting-room.html.twig', [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.registration.payment', ['id' => $user->getId()]),
            'txn_id' => $request->getSession()->get('txn_id') // For crypto payments
        ]);
    }

    #[Route('/register/check-payment-status/{id}', name: 'app.check_payment_status')]
    public function checkPaymentStatus(User $user): Response
    {
        $status = $user->getRegistrationPaymentStatus();

        if ($status === 'completed') {
            return $this->json([
                'status' => 'completed',
                'redirect' => $this->generateUrl('landing.home')
            ]);
        }

        return $this->json([
            'status' => $status
        ]);
    }

    private function validateCsrfToken(Request $request, string $tokenId): void
    {
        $token = $request->request->get('_csrf_token') ?? $request->headers->get('X-CSRF-TOKEN');

        if (!$token) {
            $this->logger->error('Missing CSRF token', [
                'token_id' => $tokenId,
                'request_data' => $request->request->all()
            ]);
            throw $this->createAccessDeniedException('Missing CSRF token');
        }

        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->logger->error('Invalid CSRF token', [
                'token_id' => $tokenId,
                'provided_token' => $token
            ]);
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }

    #[Route('/webhook/coinpayments', name: 'app.webhook.coinpayments', methods: ['POST'])]
    public function coinPaymentsWebhook(Request $request): Response
    {
        try {
            $ipnData = $request->request->all();
            $hmac = $request->headers->get('HMAC');

            // Verify IPN signature
            if (!$this->registrationPaymentService->verifyCoinPaymentsIpn($ipnData, $hmac)) {
                throw new \Exception('Invalid IPN signature');
            }

            // Handle different transaction statuses
            // 100 = complete, 2 = pending, -1 = cancelled/timed out
            if (isset($ipnData['ipn_type']) && $ipnData['ipn_type'] === 'api') {
                switch ((int) $ipnData['status']) {
                    case 100: // Payment completed
                        if ($ipnData['merchant'] === $this->getParameter('coinpayments.merchant_id')) {
                            $this->registrationPaymentService->handlePaymentSuccess(
                                $this->userRepository->find($ipnData['item_number']),
                                'coinpayments'
                            );
                        }
                        break;

                    case -1: // Payment cancelled/timeout
                        $user = $this->userRepository->find($ipnData['item_number']);
                        if ($user) {
                            $user->setRegistrationPaymentStatus('failed');
                            $this->userRepository->save($user, true);
                        }
                        break;
                }
            }

            return new Response('IPN handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('CoinPayments IPN error', [
                'error' => $e->getMessage(),
                'ipn_data' => $request->request->all()
            ]);
            return new Response('IPN error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
