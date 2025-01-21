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
        // For initial registration, skip matrix position validation
        // We'll store minimal metadata until matrix position is set
        $membership = new Membership();
        $membership->setUser($user)
            ->setAmount(Membership::ANNUAL_FEE)
            ->setStartDate(new \DateTimeImmutable())
            ->setStatus(Membership::STATUS_ACTIVE)
            ->setMetadata([
                'registration_type' => 'initial',
                'payment_method' => $paymentMethod
            ]);

        if ($paymentMethod === 'stripe') {
            $membership->setStripePaymentIntentId($transactionId);
        } elseif ($paymentMethod === 'coinpayments') {
            $membership->setCoinpaymentsTxnId($transactionId);
            if ($cryptoDetails) {
                $membership->setCryptoCurrency($cryptoDetails['crypto_currency'])
                    ->setCryptoAmount($cryptoDetails['crypto_amount']);
            }
        }

        $user->setHasPaidAnnualFee(true);

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
        // Annual membership fee is fixed at 25€
        return Membership::ANNUAL_FEE;
    }

    public function processMembershipRenewal(
        User $user,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Membership {
        // Validate matrix position and minimum depth requirement for renewals only
        if (!$user->getMatrixPosition() || !$user->getMatrixDepth()) {
            throw new \RuntimeException('User must be placed in matrix to renew membership');
        }

        if ($user->getMatrixDepth() < 3) {
            throw new \RuntimeException('User must have at least 4 matrix levels to renew membership');
        }

        $membership = new Membership();
        $membership->setUser($user)
            ->setAmount($this->getRenewalAmount())
            ->setStartDate(new \DateTimeImmutable())
            ->setStatus(Membership::STATUS_ACTIVE)
            ->setMetadata([
                'matrix_position' => $user->getMatrixPosition(),
                'matrix_depth' => $user->getMatrixDepth(),
                'current_flower' => $user->getCurrentFlower()->getName(),
                'renewal_type' => 'matrix_based'
            ]);

        if ($paymentMethod === 'stripe') {
            $membership->setStripePaymentIntentId($transactionId);
        } elseif ($paymentMethod === 'coinpayments') {
            $membership->setCoinpaymentsTxnId($transactionId);
            if ($cryptoDetails) {
                $membership->setCryptoCurrency($cryptoDetails['crypto_currency'])
                    ->setCryptoAmount($cryptoDetails['crypto_amount']);
            }
        }

        // Update user's membership status
        $user->setHasPaidAnnualFee(true);

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $this->logger->info('Membership renewed', [
            'user_id' => $user->getId(),
            'membership_id' => $membership->getId(),
            'payment_method' => $paymentMethod,
            'matrix_position' => $user->getMatrixPosition(),
            'matrix_depth' => $user->getMatrixDepth(),
            'flower_level' => $user->getCurrentFlower()->getName()
        ]);

        return $membership;
    }

    public function getLatestMembership(User $user): ?Membership
    {
        /** @var MembershipRepository $repository */
        $repository = $this->entityManager->getRepository(Membership::class);
        return $repository->findLatestByUser($user);
    }

    public function getMembershipHistory(User $user): array
    {
        return $this->entityManager->getRepository(Membership::class)
            ->findBy(
                ['user' => $user],
                ['startDate' => 'DESC']
            );
    }
}
