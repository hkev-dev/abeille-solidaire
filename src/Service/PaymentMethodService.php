<?php

namespace App\Service;

use App\Entity\User;
use Stripe\StripeClient;
use Psr\Log\LoggerInterface;
use App\Entity\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Payment\CoinPaymentsService;

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
            // Get all local payment methods
            $localMethods = $this->entityManager->getRepository(PaymentMethod::class)
                ->findBy(['user' => $user]);

            // Process local methods and sync with Stripe if needed
            foreach ($localMethods as $method) {
                if ($method->getMethodType() === PaymentMethod::TYPE_CARD) {
                    // Add card to methods array
                    $cardData = [
                        'id' => $method->getId(),
                        'last4' => $method->getLastFour(),
                        'brand' => $method->getCardBrand(),
                        'expMonth' => null,
                        'expYear' => null,
                        'isDefault' => $method->isDefault(),
                        'stripeId' => $method->getStripePaymentMethodId()
                    ];

                    // If there's a Stripe ID, try to get expiration details
                    if ($method->getStripePaymentMethodId() && $stripeCustomerId = $this->getStripeCustomerId($user)) {
                        try {
                            $stripeCard = $this->stripe->paymentMethods->retrieve($method->getStripePaymentMethodId());
                            $cardData['expMonth'] = $stripeCard->card->exp_month;
                            $cardData['expYear'] = $stripeCard->card->exp_year;
                        } catch (\Exception $e) {
                            $this->logger->warning('Error fetching Stripe card details', [
                                'user_id' => $user->getId(),
                                'stripe_payment_method_id' => $method->getStripePaymentMethodId(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    $methods['cards'][] = $cardData;
                } elseif ($method->getMethodType() === PaymentMethod::TYPE_CRYPTO) {
                    $methods['crypto'][] = [
                        'id' => $method->getId(),
                        'currency' => $method->getCryptoCurrency(),
                        'address' => $method->getCryptoAddress(),
                        'isDefault' => $method->isDefault()
                    ];
                }
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

    private function createLocalCardRecord(User $user, $stripeCard): void
    {
        try {
            $method = new PaymentMethod();
            $method->setUser($user)
                ->setMethodType(PaymentMethod::TYPE_CARD)
                ->setStripePaymentMethodId($stripeCard->id)
                ->setLastFour($stripeCard->card->last4)
                ->setCardBrand($stripeCard->card->brand);

            $this->entityManager->persist($method);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Error creating local card record', [
                'user_id' => $user->getId(),
                'stripe_card_id' => $stripeCard->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getSupportedCryptoCurrencies(): array
    {
        try {
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
            
            // Attach payment method to customer in Stripe
            $paymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);

            // Create PaymentMethod entity
            $method = new PaymentMethod();
            $method->setUser($user)
                ->setMethodType('card')
                ->setStripePaymentMethodId($paymentMethodId)
                ->setLastFour($paymentMethod->card->last4)
                ->setCardBrand($paymentMethod->card->brand);

            // If this is the first payment method, set it as default
            if (count($this->getUserPaymentMethods($user)['cards']) === 0) {
                $method->setIsDefault(true);
            }

            $this->entityManager->persist($method);
            $this->entityManager->flush();

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
            // Validate address with CoinPayments
            $isValid = $this->coinPayments->validateAddress([
                'currency' => $currency,
                'address' => $address
            ]);

            if (!$isValid) {
                throw new \InvalidArgumentException('Invalid cryptocurrency address');
            }

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

    public function setDefaultPaymentMethod(int $methodId, User $user): bool
    {
        try {
            $method = $this->entityManager->getRepository(PaymentMethod::class)
                ->findOneBy(['id' => $methodId, 'user' => $user]);

            if (!$method) {
                throw new \Exception('Payment method not found');
            }

            // Reset all payment methods to non-default
            $existingMethods = $this->entityManager->getRepository(PaymentMethod::class)
                ->findBy(['user' => $user, 'methodType' => $method->getMethodType()]);

            foreach ($existingMethods as $existingMethod) {
                $existingMethod->setIsDefault(false);
            }

            // Set the new default
            $method->setIsDefault(true);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error setting default payment method', [
                'user_id' => $user->getId(),
                'method_id' => $methodId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletePaymentMethod(int $methodId, User $user): bool
    {
        try {
            $method = $this->entityManager->getRepository(PaymentMethod::class)
                ->findOneBy(['id' => $methodId, 'user' => $user]);

            if (!$method) {
                throw new \Exception('Moyen de paiement introuvable');
            }

            if ($method->isDefault()) {
                throw new \Exception('Impossible de supprimer le moyen de paiement par défaut');
            }

            // If it's a Stripe card, detach it from Stripe first
            if ($method->getMethodType() === PaymentMethod::TYPE_CARD && 
                $method->getStripePaymentMethodId()) {
                try {
                    $this->stripe->paymentMethods->detach(
                        $method->getStripePaymentMethodId()
                    );
                } catch (\Exception $e) {
                    // Log Stripe error but continue with local deletion
                    $this->logger->warning('Error detaching Stripe payment method', [
                        'user_id' => $user->getId(),
                        'stripe_payment_method_id' => $method->getStripePaymentMethodId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->entityManager->remove($method);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error deleting payment method', [
                'user_id' => $user->getId(),
                'method_id' => $methodId,
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
