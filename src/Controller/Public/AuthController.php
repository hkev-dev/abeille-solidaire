<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\DTO\RegistrationDTO;
use App\Form\RegistrationType;
use App\Service\ReferralService;
use App\Service\SecurityService;  // Updated import
use App\Form\PaymentSelectionType;
use App\Repository\UserRepository;
use App\Service\UserRegistrationService;
use App\Service\RegistrationPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Psr\Log\LoggerInterface;

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

        if ($request->query->has('ref')) {
            $dto->referralCode = $request->query->get('ref');
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

            $this->logger->info('CoinPayments transaction created', [
                'user_id' => $user->getId(),
                'txn_id' => $transaction['txn_id']
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'txn_id' => $transaction['txn_id'],
                    'checkout_url' => $transaction['checkout_url'],
                    'status_url' => $transaction['status_url']
                ]);
            }

            return $this->redirect($transaction['checkout_url']);
        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Failed to initialize cryptocurrency payment. Please try again.');
            return $this->redirectToRoute('app.registration.payment', ['id' => $user->getId()]);
        }
    }

    #[Route('/register/waiting-room/{id}', name: 'app.waiting_room')]
    public function waitingRoom(User $user): Response
    {
        if ($user->getRegistrationPaymentStatus() === 'completed') {
            return $this->redirectToRoute('app.login');
        }

        if ($user->getRegistrationPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.registration.payment', ['id' => $user->getId()]);
        }

        return $this->render('public/pages/auth/waiting-room.html.twig', [
            'user' => $user,
            'payment_url' => $this->generateUrl('app.registration.payment', ['id' => $user->getId()])
        ]);
    }

    #[Route('/register/check-payment-status/{id}', name: 'app.check_payment_status')]
    public function checkPaymentStatus(User $user): Response
    {
        return $this->json([
            'status' => $user->getRegistrationPaymentStatus()
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

    #[Route('/webhook/stripe', name: 'app.webhook.stripe', methods: ['POST'])]
    public function stripeWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $sigHeader,
                $this->getParameter('stripe.webhook_secret')
            );

            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;
                if ($paymentIntent->metadata->payment_type === 'registration') {
                    $this->registrationPaymentService->handlePaymentSuccess(
                        $this->userRepository->find($paymentIntent->metadata->user_id),
                        'stripe'
                    );
                }
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
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
