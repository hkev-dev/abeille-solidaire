<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MembershipService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function createInitialMembership(
        User $user,
        string $paymentMethod,
        ?string $transactionId = null,
        ?array $cryptoDetails = null
    ): Membership {
        $membership = new Membership();
        $membership->setUser($user);

        if ($paymentMethod === 'stripe') {
            $membership->setStripePaymentIntentId($transactionId);
        } elseif ($paymentMethod === 'coinpayments') {
            $membership->setCoinpaymentsTxnId($transactionId);
            if ($cryptoDetails) {
                $membership->setCryptoCurrency($cryptoDetails['crypto_currency']);
                $membership->setCryptoAmount($cryptoDetails['crypto_amount']);
            }
        }

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $this->logger->info('Initial membership created', [
            'user_id' => $user->getId(),
            'membership_id' => $membership->getId(),
            'payment_method' => $paymentMethod
        ]);

        return $membership;
    }
}
