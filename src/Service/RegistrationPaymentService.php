<?php

namespace App\Service;

use Stripe\Stripe;
use App\Entity\User;
use CoinpaymentsAPI;
use Stripe\PaymentIntent;
use Psr\Log\LoggerInterface;
use App\Exception\WebhookException;
use App\Event\UserRegistrationEvent;
use App\Repository\FlowerRepository;
use App\Event\MembershipRenewalEvent;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\RegistrationPaymentCompletedEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationPaymentService
{
    private const PAYMENT_AMOUNTS = [
        'registration' => 25.00,
        'membership' => 25.00
    ];
    private CoinpaymentsAPI $coinPayments;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationService $donationService,
        private readonly MembershipService $membershipService,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly FlowerRepository $flowerRepository // Add this
    ) {
        Stripe::setApiKey($this->params->get('stripe.secret_key'));

        // Initialize CoinPayments API
        $this->coinPayments = new CoinpaymentsAPI(
            $this->params->get('coinpayments.private_key'),
            $this->params->get('coinpayments.public_key'),
            'json'  // Response format
        );
    }

    public function createStripePaymentIntent(
        User $user,
        string $paymentType = 'registration',
        bool $includeAnnualMembership = false
    ): array {
        if (!isset(self::PAYMENT_AMOUNTS[$paymentType])) {
            throw new \InvalidArgumentException('Invalid payment type');
        }

        $amount = self::PAYMENT_AMOUNTS[$paymentType];
        if ($includeAnnualMembership) {
            $amount += self::PAYMENT_AMOUNTS['membership'];
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'eur',
                'metadata' => [
                    'user_id' => $user->getId(),
                    'payment_type' => $paymentType,
                    'include_membership' => $includeAnnualMembership ? 'true' : 'false'
                ]
            ]);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'amount' => $amount
            ];
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'payment_type' => $paymentType,
                'include_membership' => $includeAnnualMembership
            ]);
            throw $e;
        }
    }

    public function createCoinPaymentsTransaction(
        User $user,
        string $paymentType = 'registration',
        string $currency = 'BTC',
        bool $includeAnnualMembership = false
    ): array {
        if (!isset(self::PAYMENT_AMOUNTS[$paymentType])) {
            throw new \InvalidArgumentException('Invalid payment type');
        }

        $amount = self::PAYMENT_AMOUNTS[$paymentType];
        if ($includeAnnualMembership) {
            $amount += self::PAYMENT_AMOUNTS['membership'];
        }

        try {
            $result = $this->coinPayments->CreateComplexTransaction(
                amount: $amount,
                currency1: 'EUR',
                currency2: $currency,
                buyer_email: $user->getEmail(),
                address: "",
                buyer_name: $user->getFirstname() . ' ' . $user->getLastname(),
                item_name: $includeAnnualMembership ? 'Registration + Annual Membership' : 'Registration',
                item_number: "{$user->getId()}_{$paymentType}",
                invoice: strtoupper(substr($paymentType, 0, 3)) . '-' . $user->getId(),
                custom: json_encode([
                    'type' => $paymentType,
                    'include_membership' => $includeAnnualMembership
                ]),
                ipn_url: $this->router->generate('app.webhook.coinpayments', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($result['error'] !== 'ok') {
                throw new \RuntimeException($result['error']);
            }

            $transaction = $result['result'];

            return [
                'txn_id' => $transaction['txn_id'],
                'status_url' => $transaction['status_url'],
                'checkout_url' => $transaction['checkout_url'],
                'amount' => $transaction['amount'],
                'address' => $transaction['address'],
                'confirms_needed' => $transaction['confirms_needed'],
                'timeout' => $transaction['timeout'],
                'qrcode_url' => $transaction['qrcode_url'],
                'currency1' => 'EUR',
                'currency2' => $currency,
                'status' => $transaction['status'] ?? 0,
                'payment_type' => $paymentType
            ];

        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'payment_type' => $paymentType
            ]);
            throw $e;
        }
    }

    public function verifyCoinPaymentsIpn(array $ipnData, string $hmac): bool
    {
        if (!isset($ipnData['ipn_mode'], $ipnData['merchant'])) {
            throw new WebhookException('Invalid IPN data format');
        }

        if ($ipnData['ipn_mode'] !== 'hmac') {
            throw new WebhookException('Invalid IPN mode');
        }

        $merchantId = $this->params->get('coinpayments.merchant_id');
        if ($ipnData['merchant'] !== $merchantId) {
            throw new WebhookException('Invalid merchant ID');
        }

        // Verify HMAC
        $rawPostData = file_get_contents('php://input');
        if (empty($rawPostData)) {
            throw new WebhookException('Empty POST data');
        }

        $calculatedHmac = hash_hmac(
            'sha512',
            $rawPostData,
            $this->params->get('coinpayments.ipn_secret')
        );

        return hash_equals($calculatedHmac, $hmac);
    }

    public function handlePaymentSuccess(
        User $user,
        string $paymentMethod,
        string $paymentType,
        ?string $transactionId = null,
        bool $includeAnnualMembership = false
    ): void {
        $this->entityManager->beginTransaction();

        try {
            // Find matrix position first
            $violette = $this->flowerRepository->findOneBy(['name' => 'Violette']);
            if (!$violette) {
                throw new \RuntimeException('Violette flower not found');
            }

            try {
                $position = $this->matrixPlacementService->findNextAvailablePosition($violette);
                if (!$position['parent'] && $position['position'] !== 1) {
                    throw new \RuntimeException('Could not determine parent position');
                }
            } catch (\RuntimeException $e) {
                $this->logger->error('Matrix placement failed', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
                throw new \RuntimeException('Could not find available matrix position: ' . $e->getMessage());
            }

            // Update user's matrix information
            $user->setMatrixDepth($position['depth'])
                ->setMatrixPosition($position['position'])
                ->setParent($position['parent']);

            $cryptoDetails = null;
            if ($paymentMethod === 'coinpayments' && $transactionId) {
                $cryptoDetails = $this->getCoinPaymentsTransactionDetails($transactionId);
            }

            // Handle registration payment
            $user->setRegistrationPaymentStatus('completed')
                ->setWaitingSince(null)
                ->setIsVerified(true)
                ->setCurrentFlower($violette);

            // Create registration donation
            $registrationDonation = $this->donationService->createRegistrationDonation(
                $user,
                $paymentMethod,
                $transactionId,
                $cryptoDetails
            );

            // Handle membership if included
            $membership = null;
            if ($includeAnnualMembership) {
                $user->setHasPaidAnnualFee(true);
                $membership = $this->membershipService->createInitialMembership(
                    $user,
                    $paymentMethod,
                    $transactionId,
                    $cryptoDetails
                );
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Dispatch events
            $event = new UserRegistrationEvent(
                $user,
                $registrationDonation,
                $paymentMethod,
                $membership
            );
            $this->eventDispatcher->dispatch($event, UserRegistrationEvent::PAYMENT_COMPLETED);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function handlePaymentFailure(User $user, string $paymentMethod, string $errorMessage = null): void
    {
        try {
            $user->setRegistrationPaymentStatus('failed');
            $this->entityManager->flush();

            $event = new UserRegistrationEvent($user, errorMessage: $errorMessage);
            $this->eventDispatcher->dispatch($event, UserRegistrationEvent::PAYMENT_FAILED);

            $this->logger->warning('Registration payment failed', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'error' => $errorMessage
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process payment failure', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleRegistrationSuccess(
        User $user,
        string $paymentMethod,
        ?string $transactionId,
        ?array $cryptoDetails
    ): void {
        // Existing registration success logic
        $user->setRegistrationPaymentStatus('completed')
            ->setWaitingSince(null)
            ->setIsVerified(true);

        $donation = $this->donationService->createRegistrationDonation(
            $user,
            $paymentMethod,
            $transactionId,
            $cryptoDetails
        );

        $membership = $this->membershipService->createInitialMembership(
            $user,
            $paymentMethod,
            $transactionId,
            $cryptoDetails
        );

        $event = new UserRegistrationEvent($user, $donation, $paymentMethod, $membership);
        $this->eventDispatcher->dispatch($event, UserRegistrationEvent::PAYMENT_COMPLETED);
    }

    private function handleMembershipRenewalSuccess(
        User $user,
        string $paymentMethod,
        ?string $transactionId,
        ?array $cryptoDetails
    ): void {
        $membership = $this->membershipService->processMembershipRenewal(
            $user,
            $paymentMethod,
            $transactionId,
            $cryptoDetails
        );

        $event = new MembershipRenewalEvent($user, $membership);
        $this->eventDispatcher->dispatch($event, MembershipRenewalEvent::PAYMENT_COMPLETED);
    }

    private function getCoinPaymentsTransactionDetails(string $txnId): ?array
    {
        try {
            $result = $this->coinPayments->GetTxInfoSingle($txnId);

            if ($result['error'] !== 'ok') {
                throw new \Exception($result['error']);
            }

            $transaction = $result['result'];

            return [
                'crypto_amount' => $transaction['amount'],
                'crypto_currency' => $transaction['coin'],
                'exchange_rate' => $transaction['rate'],
                'confirms_needed' => $transaction['confirms_needed'],
                'status' => $transaction['status'],
                'status_text' => $transaction['status_text'],
                'payment_address' => $transaction['payment_address']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch CoinPayments transaction details', [
                'txn_id' => $txnId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getAcceptedCryptoCurrencies(): array
    {
        try {
            $result = $this->coinPayments->GetRates();

            if ($result['error'] !== 'ok') {
                throw new \Exception($result['error'] ?? 'Failed to fetch rates');
            }

            $accepted = [];
            foreach ($result['result'] as $coin => $data) {
                // Check if currency data is valid and has the required fields
                if (!is_array($data))
                    continue;

                // The 'accepted' key might not exist in newer API versions
                // Consider all listed currencies as accepted unless explicitly marked as not accepted
                $isAccepted = $data['accepted'] ?? true;

                if ($isAccepted) {
                    $accepted[$coin] = [
                        'name' => $data['name'] ?? $coin,
                        'rate_btc' => $data['rate_btc'] ?? '0',
                        'tx_fee' => $data['tx_fee'] ?? '0',
                        'confirms_needed' => $data['confirms'] ?? 3,
                        'is_fiat' => $data['is_fiat'] ?? 0,
                        'status' => $data['status'] ?? 'online'
                    ];
                }
            }

            // Always include LTCT for testing environments
            if (
                ($this->params->get('kernel.environment') === 'dev' ||
                    $this->params->get('kernel.environment') === 'test')
            ) {
                $accepted['LTCT'] = [
                    'name' => 'Litecoin Testnet',
                    'rate_btc' => '0.00000000',
                    'tx_fee' => '0.00000000',
                    'confirms_needed' => 3,
                    'is_fiat' => 0,
                    'status' => 'online'
                ];
            }

            // Log the available currencies for debugging
            $this->logger->debug('Available cryptocurrencies', [
                'currencies' => array_keys($accepted)
            ]);

            return $accepted;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch accepted cryptocurrencies', [
                'error' => $e->getMessage()
            ]);

            // In development/test, return LTCT as fallback
            if ($this->params->get('kernel.environment') !== 'prod') {
                return [
                    'LTCT' => [
                        'name' => 'Litecoin Testnet',
                        'rate_btc' => '0.00000000',
                        'tx_fee' => '0.00000000',
                        'confirms_needed' => 3,
                        'is_fiat' => 0,
                        'status' => 'online'
                    ]
                ];
            }

            return [];
        }
    }
}
