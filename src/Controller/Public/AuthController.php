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
use Symfony\Component\HttpFoundation\JsonResponse;
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
            $includeAnnualMembership = $data['include_annual_membership'] ?? false;

            if ($data['payment_method'] === 'stripe') {
                try {
                    $stripeData = $this->registrationPaymentService->createStripePaymentIntent(
                        user: $user,
                        paymentType: 'registration',
                        includeAnnualMembership: $includeAnnualMembership
                    );

                    // Store payment preferences in session
                    $request->getSession()->set('payment_method', 'stripe');
                    $request->getSession()->set('include_annual_membership', $includeAnnualMembership);

                    return $this->json([
                        'clientSecret' => $stripeData['clientSecret'],
                        'amount' => $stripeData['amount']
                    ]);
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $form = $this->createForm(PaymentSelectionType::class, null, [
            'action' => $this->generateUrl('app.registration.payment', ['id' => $user->getId()]),
        ]);

        $form->handleRequest($request);

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
        $includeAnnualMembership = filter_var(
            $request->request->get('include_annual_membership', false),
            FILTER_VALIDATE_BOOLEAN
        );

        try {
            $transaction = $this->registrationPaymentService->createCoinPaymentsTransaction(
                user: $user,
                paymentType: 'registration',
                currency: $request->request->get('currency'),
                includeAnnualMembership: $includeAnnualMembership
            );

            // Store payment preferences in session
            $request->getSession()->set('payment_method', 'crypto');
            $request->getSession()->set('include_annual_membership', $includeAnnualMembership);
            $request->getSession()->set('txn_id', $transaction['txn_id']);

            return $this->json($transaction);
        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'include_membership' => $includeAnnualMembership
            ]);

            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
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
            // Get webhook data
            $ipnData = $request->request->all();
            $hmac = $request->headers->get('HMAC');
            $webhookId = uniqid('whk_', true);

            $this->logger->info('Webhook received', [
                'webhook_id' => $webhookId,
                'ipn_type' => $ipnData['ipn_type'] ?? 'unknown',
                'txn_id' => $ipnData['txn_id'] ?? null,
                'timestamp' => time()
            ]);

            // Verify IPN signature
            if (!$this->registrationPaymentService->verifyCoinPaymentsIpn($ipnData, $hmac)) {
                $this->logger->warning('Invalid webhook signature', [
                    'webhook_id' => $webhookId,
                    'ipn_data' => $ipnData
                ]);
                return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
            }

            // Check for duplicate webhook
            $processingKey = "webhook_processing_{$ipnData['txn_id']}";
            if (!$this->lockWebhook($processingKey)) {
                $this->logger->info('Duplicate webhook detected', [
                    'webhook_id' => $webhookId,
                    'txn_id' => $ipnData['txn_id']
                ]);
                return new Response('Webhook already processing', Response::HTTP_TOO_MANY_REQUESTS);
            }

            $startTime = microtime(true);

            try {
                if (isset($ipnData['ipn_type']) && $ipnData['ipn_type'] === 'api') {
                    $user = $this->userRepository->find($ipnData['item_number']);

                    if (!$user) {
                        throw new \Exception("User not found for item_number: {$ipnData['item_number']}");
                    }

                    // Parse custom field to get membership inclusion
                    $custom = json_decode($ipnData['custom'] ?? '{}', true);
                    $includeAnnualMembership = filter_var($custom['include_membership'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $paymentStatus = (int) $ipnData['status'];
                    $this->logger->info('Processing payment status', [
                        'webhook_id' => $webhookId,
                        'user_id' => $user->getId(),
                        'status' => $paymentStatus,
                        'status_text' => $ipnData['status_text'] ?? null,
                        'include_membership' => $includeAnnualMembership
                    ]);

                    match ($paymentStatus) {
                        // Complete
                        100 => $this->registrationPaymentService->handlePaymentSuccess(
                            user: $user,
                            paymentMethod: 'coinpayments',
                            paymentType: 'registration',
                            transactionId: $ipnData['txn_id'],
                            includeAnnualMembership: $includeAnnualMembership
                        ),
                        // Cancelled/Timeout
                        -1 => $this->registrationPaymentService->handlePaymentFailure(
                            user: $user,
                            paymentMethod: 'coinpayments',
                            errorMessage: $ipnData['status_text'] ?? 'Payment cancelled or timed out'
                        ),
                        // Pending
                        0 => $this->logger->info('Payment pending', [
                            'webhook_id' => $webhookId,
                            'user_id' => $user->getId()
                        ]),
                        // Other statuses
                        default => $this->logger->warning('Unexpected payment status', [
                            'webhook_id' => $webhookId,
                            'status' => $paymentStatus,
                            'user_id' => $user->getId()
                        ])
                    };

                    $processingTime = microtime(true) - $startTime;
                    $this->logger->info('Webhook processed', [
                        'webhook_id' => $webhookId,
                        'processing_time' => $processingTime,
                        'user_id' => $user->getId()
                    ]);
                }
            } finally {
                // Always release the lock
                $this->releaseWebhook($processingKey);
            }

            return new Response('IPN Processed', Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing error', [
                'webhook_id' => $webhookId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ipn_data' => $ipnData ?? []
            ]);

            // Return 202 to avoid webhook retries for business logic errors
            $statusCode = $e instanceof WebhookException
                ? Response::HTTP_ACCEPTED
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return new Response('IPN Error: ' . $e->getMessage(), $statusCode);
        }
    }

    private function lockWebhook(string $key): bool
    {
        // Using APCu for simplicity, but could use Redis or other distributed lock mechanism
        if (apcu_exists($key)) {
            return false;
        }
        return apcu_add($key, true, 300); // 5-minute lock
    }

    private function releaseWebhook(string $key): void
    {
        apcu_delete($key);
    }

    #[Route('/registration/crypto/currencies', name: 'app.registration.crypto.currencies', methods: ['GET'])]
    public function getCryptoCurrencies(): JsonResponse
    {
        try {
            $currencies = $this->registrationPaymentService->getAcceptedCryptoCurrencies();

            // For testing environments, ensure LTCT is available
            if (
                empty($currencies) &&
                ($this->getParameter('kernel.environment') === 'dev' ||
                    $this->getParameter('kernel.environment') === 'test')
            ) {
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
