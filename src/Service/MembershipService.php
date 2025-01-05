<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use App\Repository\MembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MembershipService
{
    private const MEMBERSHIP_DURATION = 'P1Y'; // 1 year interval

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

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

    public function isExpired(User $user): bool
    {
        /** @var MembershipRepository $repository */
        $repository = $this->entityManager->getRepository(Membership::class);
        $latestMembership = $repository->findLatestByUser($user);

        if (!$latestMembership) {
            return true;
        }

        return !$latestMembership->isActive();
    }

    public function getRenewalAmount(): float
    {
        return 25.0; // Annual membership fee
    }

    public function processMembershipRenewal(
        User $user,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Membership {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setAmount($this->getRenewalAmount());

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

        $this->logger->info('Membership renewed', [
            'user_id' => $user->getId(),
            'membership_id' => $membership->getId(),
            'payment_method' => $paymentMethod
        ]);

        return $membership;
    }

    public function getLatestMembership(User $user): ?Membership
    {
        /** @var MembershipRepository $repository */
        $repository = $this->entityManager->getRepository(Membership::class);
        return $repository->findLatestByUser($user);
    }
}
