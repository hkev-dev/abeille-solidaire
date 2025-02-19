<?php

namespace App\Controller\Public;

use App\Entity\Donation;
use App\Entity\User;
use App\DTO\RegistrationDTO;
use App\Repository\DonationRepository;
use App\Service\Payment\PaymentServiceInterface;
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
        $this->securityService->checkRegistrationThrottle();

        if ($this->getUser()) {
            return $this->redirectToRoute('app.user.dashboard');
        }
        
        $dto = new RegistrationDTO();
        $form = $this->createForm(RegistrationType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->securityService->verifyRecaptcha($dto->recaptcha)) {
                $this->addFlash('error', 'Invalid reCAPTCHA. Please try again.');
                return $this->redirectToRoute('app.register');
            }

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

        /** @var User $user */
        if ($user->getMainDonation() && $user->getMainDonation()->getPaymentStatus() === 'completed') {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // Handle AJAX requests for payment creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);
            $includeAnnualMembership = $data['include_annual_membership'] ?? false;
            $paymentMethod = $data['payment_method'] ?? 'stripe';

            try {
                $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);
                if (isset($data['currency'])) {
                    $user->setPaymentCurrency($data['currency']);
                }
                $paymentData = $paymentService->createRegistrationPayment($user, $includeAnnualMembership);
                if (isset($data['currency'])) {
                    $paymentData["currency"] = $data['currency'];
                }

                // Store payment preferences in session
                $request->getSession()->set('payment_method', $paymentMethod);
                $request->getSession()->set('include_annual_membership', $includeAnnualMembership);

                if (isset($paymentData['payment_reference']) || isset($paymentData['txn_id'])) {
                    $request->getSession()->set('payment_reference', $paymentData['payment_reference'] ?? $paymentData['txn_id']);
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
    public function waitingRoom(Request $request, DonationRepository $donationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        $donation = $donationRepository->find($request->query->get('id'));

        if (!$donation) {
            throw $this->createNotFoundException('Donation not found');
        }

        if ($donation->getPaymentStatus() === 'completed') {
            return $this->redirectToRoute('app.user.dashboard');
        } else if ($donation->getPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.registration.payment');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');
        $params = [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.registration.payment'),
            'payment_reference' => $request->getSession()->get('payment_reference'),
            'donation' => $donation,
        ];

        if ($data = $request->query->get('cp_data')){
            $params['cp_data'] = json_decode($data, true);

            return $this->render('public/pages/auth/coinpayments-waiting-room.html.twig', $params);
        }else if ($paymentMethod === 'stripe'){

            return $this->render('public/pages/auth/waiting-room.html.twig', $params);
        }else{
            $this->addFlash('error', 'Payment method not supported');
            return $this->redirectToRoute('app.user.dashboard');
        }
    }

    #[Route('/register/check-payment-status/{id}', name: 'app.check_payment_status')]
    public function checkPaymentStatus(Request $request, Donation $donation): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'redirect' => $this->generateUrl('app.login')
            ], Response::HTTP_UNAUTHORIZED);
        }

        $status = $donation->getPaymentStatus();

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
