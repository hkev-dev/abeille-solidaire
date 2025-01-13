<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;

class PaymentMethodService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StripeClient $stripe,
        private readonly CoinPaymentsService $coinPayments,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getUserPaymentMethods(User $user): array
    {
        $methods = [
            'cards' => [],
            'crypto' => []
        ];

        try {
            // Get Stripe cards if user has a Stripe customer ID
            if ($stripeCustomerId = $this->getStripeCustomerId($user)) {
                $cards = $this->stripe->paymentMethods->all([
                    'customer' => $stripeCustomerId,
                    'type' => 'card'
                ]);

                foreach ($cards->data as $card) {
                    $methods['cards'][] = [
                        'id' => $card->id,
                        'last4' => $card->card->last4,
                        'brand' => $card->card->brand,
                        'expMonth' => $card->card->exp_month,
                        'expYear' => $card->card->exp_year,
                        'isDefault' => $card->id === $user->getDefaultPaymentMethodId()
                    ];
                }
            }

            // Get crypto wallets from database
            $cryptoMethods = $this->entityManager->getRepository(PaymentMethod::class)
                ->findBy(['user' => $user, 'methodType' => 'crypto']);

            foreach ($cryptoMethods as $method) {
                $methods['crypto'][] = [
                    'id' => $method->getId(),
                    'currency' => $method->getCryptoCurrency(),
                    'address' => $method->getCryptoAddress(),
                    'isDefault' => $method->isDefault()
                ];
            }

            return $methods;

        } catch (\Exception $e) {
            $this->logger->error('Error fetching payment methods', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSupportedCryptoCurrencies(): array
    {
        try {
            // Fix: Change method name to match CoinPaymentsService
            return $this->coinPayments->getAcceptedCurrencies();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching supported cryptocurrencies', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function addCardPaymentMethod(User $user, string $paymentMethodId): bool
    {
        try {
            $stripeCustomerId = $this->getStripeCustomerId($user, true);
            
            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $stripeCustomerId
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error adding card payment method', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addCryptoWallet(User $user, string $currency, string $address): bool
    {
        try {
            $method = new PaymentMethod();
            $method->setUser($user)
                  ->setMethodType('crypto')
                  ->setCryptoCurrency($currency)
                  ->setCryptoAddress($address);

            $this->entityManager->persist($method);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error adding crypto wallet', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getStripeCustomerId(User $user, bool $createIfNotExists = false): ?string
    {
        if ($user->getStripeCustomerId()) {
            return $user->getStripeCustomerId();
        }

        if (!$createIfNotExists) {
            return null;
        }

        try {
            $customer = $this->stripe->customers->create([
                'email' => $user->getEmail(),
                'name' => $user->getFullName(),
                'metadata' => [
                    'user_id' => $user->getId()
                ]
            ]);

            $user->setStripeCustomerId($customer->id);
            $this->entityManager->flush();

            return $customer->id;
        } catch (\Exception $e) {
            $this->logger->error('Error creating Stripe customer', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
