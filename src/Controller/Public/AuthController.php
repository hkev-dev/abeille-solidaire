<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\DTO\RegistrationDTO;
use Psr\Log\LoggerInterface;
use App\Form\RegistrationType;
use App\Service\SecurityService;
use App\Form\PaymentSelectionType;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Service\Payment\PaymentFactory;
use App\Service\UserRegistrationService;
use App\Service\Payment\CoinPaymentsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationService $userRegistrationService,
        private readonly SecurityService $securityService,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly PaymentFactory $paymentFactory,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly FormLoginAuthenticator $authenticator
    ) {
    }

    #[Route('/login', name: 'app.login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('public/pages/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app.logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/register', name: 'app.register')]
    public function register(Request $request): Response
    {
//        $this->securityService->checkRegistrationThrottle();

        $dto = new RegistrationDTO();
        $form = $this->createForm(RegistrationType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
//            if (!$this->securityService->verifyRecaptcha($dto->recaptcha)) {
//                $this->addFlash('error', 'Invalid reCAPTCHA. Please try again.');
//                return $this->redirectToRoute('app.register');
//            }

            try {
                $user = $this->userRegistrationService->registerUser($dto);

                // Auto login the user right after registration
                $this->userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );

                $this->addFlash('success', 'Registration successful! Please complete your payment to be placed in the matrix system.');
                return $this->redirectToRoute('app.registration.payment');
            } catch (\Exception $e) {
                $this->logger->error('Registration failed', [
                    'error' => $e->getMessage(),
                    'email' => $dto->email
                ]);
                $this->addFlash('error', 'Registration failed. Please try again.');
                return $this->redirectToRoute('app.register');
            }
        }

        return $this->render('public/pages/auth/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/register/payment', name: 'app.registration.payment', methods: ['GET', 'POST'])]
    public function paymentSelection(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        if ($user->getRegistrationPaymentStatus() !== 'pending') {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // Handle AJAX requests for payment creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);
            $includeAnnualMembership = $data['include_annual_membership'] ?? false;
            $paymentMethod = $data['payment_method'] ?? 'stripe';

            try {
                $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);
                $paymentData = $paymentService->createRegistrationPayment($user, $includeAnnualMembership);

                // Store payment preferences in session
                $request->getSession()->set('payment_method', $paymentMethod);
                $request->getSession()->set('include_annual_membership', $includeAnnualMembership);

                if (isset($paymentData['txn_id'])) {
                    $request->getSession()->set('txn_id', $paymentData['txn_id']);
                }

                return $this->json($paymentData);
            } catch (\Exception $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        $form = $this->createForm(PaymentSelectionType::class);
        $form->handleRequest($request);

        return $this->render('public/pages/auth/payment-selection.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/register/waiting-room', name: 'app.waiting_room')]
    public function waitingRoom(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        if ($user->getRegistrationPaymentStatus() === 'completed') {
            return $this->redirectToRoute('app.user.dashboard');
        }

        if ($user->getRegistrationPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.registration.payment');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');

        return $this->render('public/pages/auth/waiting-room.html.twig', [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.registration.payment'),
            'txn_id' => $request->getSession()->get('txn_id')
        ]);
    }

    #[Route('/register/check-payment-status', name: 'app.check_payment_status')]
    public function checkPaymentStatus(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'redirect' => $this->generateUrl('app.login')
            ], Response::HTTP_UNAUTHORIZED);
        }

        $status = $user->getRegistrationPaymentStatus();

        if ($status === 'completed') {
            return $this->json([
                'status' => 'completed',
                'redirect' => $this->generateUrl('app.user.dashboard')
            ]);
        }

        return $this->json([
            'status' => $status
        ]);
    }

    #[Route('/registration/crypto/currencies', name: 'app.registration.crypto.currencies', methods: ['GET'])]
    public function getCryptoCurrencies(): JsonResponse
    {
        try {
            /**
             * @var CoinPaymentsService
             */
            $cryptoService = $this->paymentFactory->getPaymentService('coinpayments');
            $currencies = $cryptoService->getAcceptedCryptoCurrencies();

            if (empty($currencies) && $this->getParameter('kernel.environment') !== 'prod') {
                $currencies['LTCT'] = [
                    'name' => 'Litecoin Testnet',
                    'rate_btc' => '0.00000000',
                    'tx_fee' => '0.00000000',
                    'confirms_needed' => 3,
                    'is_fiat' => 0
                ];
            }

            return $this->json(['currencies' => $currencies]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to load cryptocurrencies. Please try again later.',
                'debug' => $this->getParameter('kernel.debug') ? $e->getMessage() : null
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
