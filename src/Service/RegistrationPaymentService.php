<?php

namespace App\Service;

use Stripe\Stripe;
use App\Entity\User;
use CoinpaymentsAPI;
use Stripe\PaymentIntent;
use Psr\Log\LoggerInterface;
use App\Event\UserRegistrationEvent;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\RegistrationPaymentCompletedEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationPaymentService
{
    private const REGISTRATION_FEE = 25.00;
    private CoinpaymentsAPI $coinPayments;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationService $donationService,
        private readonly MembershipService $membershipService
    ) {
        Stripe::setApiKey($this->params->get('stripe.secret_key'));

        // Initialize CoinPayments API
        $this->coinPayments = new CoinpaymentsAPI(
            $this->params->get('coinpayments.private_key'),
            $this->params->get('coinpayments.public_key'),
            'json'  // Response format
        );
    }

    public function createStripePaymentIntent(User $user): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => self::REGISTRATION_FEE * 100, // Convert to cents
                'currency' => 'eur',
                'metadata' => [
                    'user_id' => $user->getId(),
                    'payment_type' => 'registration'
                ]
            ]);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ];
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }

    public function createCoinPaymentsTransaction(User $user, string $currency = 'BTC'): array
    {
        try {
            // Create the transaction with full details
            $result = $this->coinPayments->CreateComplexTransaction(
                amount: self::REGISTRATION_FEE,
                currency1: 'EUR',
                currency2: $currency,
                buyer_email: $user->getEmail(),
                address: "",
                buyer_name: $user->getFirstname() . ' ' . $user->getLastname(),
                item_name: 'Registration Fee',
                item_number: "{$user->getId()}",
                invoice: 'REG-' . $user->getId(),
                custom: 'type=registration',
                ipn_url: $this->router->generate('app.webhook.coinpayments', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($result['error'] !== 'ok') {
                throw new \RuntimeException($result['error']);
            }

            // Store additional transaction details
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
                'status' => $transaction['status'] ?? 0
            ];

        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }

    public function verifyCoinPaymentsIpn(array $ipnData, string $hmac): bool
    {
        if (!isset($ipnData['ipn_mode']) || $ipnData['ipn_mode'] !== 'hmac') {
            return false;
        }

        $merchantId = $this->params->get('coinpayments.merchant_id');
        $ipnSecret = $this->params->get('coinpayments.ipn_secret');

        // Check the merchant ID matches
        if ($ipnData['merchant'] !== $merchantId) {
            return false;
        }

        // Calculate HMAC
        $rawPostData = file_get_contents('php://input');
        $calculatedHmac = hash_hmac('sha512', $rawPostData, $ipnSecret);

        return hash_equals($calculatedHmac, $hmac);
    }

    public function handlePaymentSuccess(User $user, string $paymentMethod, string $transactionId = null): void
    {
        try {
            // Update user status
            $user->setRegistrationPaymentStatus('completed');
            $user->setWaitingSince(null);
            $user->setIsVerified(true);

            $this->entityManager->flush();

            $cryptoDetails = null;
            if ($paymentMethod === 'coinpayments' && $transactionId) {
                $cryptoDetails = $this->getCoinPaymentsTransactionDetails($transactionId);
            }

            // Create initial donation record
            $donation = $this->donationService->createRegistrationDonation(
                $user,
                $paymentMethod,
                $transactionId,
                $cryptoDetails
            );

            // Create initial membership
            $membership = $this->membershipService->createInitialMembership(
                $user,
                $paymentMethod,
                $transactionId,
                $cryptoDetails
            );

            // Dispatch event with membership information
            $event = new UserRegistrationEvent($user, $donation, $paymentMethod, $membership);
            $this->eventDispatcher->dispatch($event, UserRegistrationEvent::PAYMENT_COMPLETED);

            $this->logger->info('Registration payment completed successfully', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process successful payment', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
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

    private function getCoinPaymentsTransactionDetails(string $txnId): ?array
    {
        try {
            $result = $this->coinPayments->GetTxInfo(['txid' => $txnId]);

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
