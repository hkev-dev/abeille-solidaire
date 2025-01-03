<?php

namespace App\Service;

use App\Event\UserRegistrationEvent;
use Stripe\Stripe;
use App\Entity\User;
use Stripe\PaymentIntent;
use Psr\Log\LoggerInterface;
use Sigismund\CoinPayments\Credentials;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use Sigismund\CoinPayments\CoinPayments;
use Sigismund\CoinPayments\IpnValidation;
use App\Event\RegistrationPaymentCompletedEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationPaymentService
{
    private const REGISTRATION_FEE = 25.00;
    private CoinPayments $coinPayments;
    private Credentials $credentials;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationService $donationService
    ) {
        Stripe::setApiKey($this->params->get('stripe.secret_key'));

        // Initialize CoinPayments credentials
        $this->credentials = new Credentials(
            $this->params->get('coinpayments.merchant_id'),
            $this->params->get('coinpayments.public_key'),
            $this->params->get('coinpayments.private_key'),
            $this->params->get('coinpayments.ipn_secret')
        );

        // Initialize CoinPayments client
        $this->coinPayments = new CoinPayments(
            $this->credentials->getMerchantID(),
            $this->credentials->getPublicKey(),
            $this->credentials->getPrivateKey(),
            $this->credentials->getIpnSecret()
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

    public function createCoinPaymentsTransaction(User $user): array
    {
        try {
            $ipnUrl = $this->router->generate('app.webhook.coinpayments', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Debug log the request parameters
            $this->logger->debug('Creating CoinPayments transaction', [
                'amount' => self::REGISTRATION_FEE,
                'currency1' => 'EUR',
                'currency2' => 'BTC',
                'buyer_email' => $user->getEmail(),
                'ipn_url' => $ipnUrl,
                'user_id' => $user->getId()
            ]);

            $transaction = $this->coinPayments->createTransaction(
                self::REGISTRATION_FEE,
                'EUR',
                'BTC',
                [

                    'buyer_email' => $user->getEmail(),
                    'item_name' => 'Registration Fee',
                    'item_number' => $user->getId(),
                    'ipn_url' => $ipnUrl,
                    'ipn_mode' => 'hmac', // Explicitly set HMAC mode
                    'format' => 'json',   // Explicitly request JSON response
                ]
            );

            // Debug log the raw response
            $this->logger->debug('CoinPayments raw response', [
                'response' => $transaction->getRawResponse(),
                'user_id' => $user->getId()
            ]);

            if (!$transaction->isSuccessful()) {
                $this->logger->error('CoinPayments transaction failed', [
                    'error' => $transaction->getError(),
                    'error_code' => $transaction->getErrorCode(),
                    'user_id' => $user->getId(),
                    'raw_response' => $transaction->getRawResponse()
                ]);
                throw new \RuntimeException('CoinPayments transaction creation failed: ' . $transaction->getError());
            }

            $result = [
                'txn_id' => $transaction->getId(),
                'status_url' => $transaction->getStatusUrl(),
                'checkout_url' => $transaction->getCheckoutUrl(),
                'amount' => $transaction->getAmount(),
                'address' => $transaction->getAddress(),
                'confirms_needed' => $transaction->getConfirmsNeeded(),
                'timeout' => $transaction->getTimeout(),
                'currency1' => 'EUR',
                'currency2' => 'BTC',
            ];

            // Log successful transaction details
            $this->logger->info('CoinPayments transaction created successfully', [
                'txn_id' => $result['txn_id'],
                'user_id' => $user->getId(),
                'amount' => $result['amount']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function verifyCoinPaymentsIpn(array $ipnData, string $hmac): bool
    {
        try {
            $validator = new IpnValidation(
                $ipnData,
                ['HTTP_HMAC' => $hmac],
                $this->credentials
            );

            return $validator->validate();
        } catch (\Exception $e) {
            $this->logger->error('CoinPayments IPN validation failed', [
                'error' => $e->getMessage(),
                'ipn_data' => $ipnData
            ]);
            return false;
        }
    }

    public function handlePaymentSuccess(User $user, string $paymentMethod, string $transactionId = null): void
    {
        try {
            // Update user status
            $user->setRegistrationPaymentStatus('completed');
            $user->setWaitingSince(null);
            $user->setIsVerified(true);

            $this->entityManager->flush();

            // Create initial donation record
            if ($transactionId) {
                $cryptoDetails = null;
                if ($paymentMethod === 'coinpayments') {
                    // Fetch transaction details from CoinPayments if needed
                    $cryptoDetails = $this->getCoinPaymentsTransactionDetails($transactionId);
                }

                $donation = $this->donationService->createRegistrationDonation(
                    $user,
                    $paymentMethod,
                    $transactionId,
                    $cryptoDetails
                );

                // Dispatch event
                $event = new UserRegistrationEvent($user, $donation, $paymentMethod);
                $this->eventDispatcher->dispatch($event, UserRegistrationEvent::PAYMENT_COMPLETED);
            }

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
            $transaction = $this->coinPayments->getTransactionInfo($txnId);

            return [
                'crypto_amount' => $transaction->getAmount(),
                'crypto_currency' => $transaction->getCurrency2(),
                'exchange_rate' => $transaction->getExchangeRate(),
                'confirms_needed' => $transaction->getConfirmsNeeded(),
                'status_url' => $transaction->getStatusUrl()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch CoinPayments transaction details', [
                'txn_id' => $txnId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
