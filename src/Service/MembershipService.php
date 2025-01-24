<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MembershipService
{
    public const ANNUAL_FEE = 25.0;
    private const MEMBERSHIP_DURATION = 'P1Y'; // 1 year
    private const GRACE_PERIOD = 'P15D'; // 15 days grace period
    
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processInitialMembership(
        User $user,
        string $paymentMethod,
        ?string $transactionId = null
    ): bool {
        try {
            $user->setHasPaidAnnualFee(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('Initial membership processed', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to process initial membership', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function processMembershipRenewal(
        User $user,
        string $paymentMethod,
        string $transactionId
    ): bool {
        if (!$user->getMatrixPosition() || $user->getMatrixDepth() < 3) {
            throw new \RuntimeException('User must have at least 4 matrix levels to renew membership');
        }

        try {
            $user->setHasPaidAnnualFee(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('Membership renewed', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'matrix_position' => $user->getMatrixPosition(),
                'matrix_depth' => $user->getMatrixDepth(),
                'flower_level' => $user->getCurrentFlower()?->getName()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to renew membership', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getLatestMembership(User $user): ?Donation
    {
        return $this->entityManager->getRepository(Donation::class)
            ->findOneBy(
                [
                    'donor' => $user,
                    'donationType' => 'membership'
                ],
                ['transactionDate' => 'DESC']
            );
    }

    public function getRenewalAmount(): float
    {
        return self::ANNUAL_FEE;
    }

    public function getMembershipHistory(User $user): array
    {
        // Get membership-related donations from the donations table
        return $this->entityManager->getRepository(Donation::class)
            ->findBy(
                ['donor' => $user, 'donationType' => 'membership'],
                ['transactionDate' => 'DESC']
            );
    }

    public function isExpired(User $user): bool
    {
        if (!$user->hasPaidAnnualFee()) {
            return true;
        }

        $lastPaymentDate = $this->getLastMembershipPaymentDate($user);
        if (!$lastPaymentDate) {
            return true;
        }

        $expiryDate = $lastPaymentDate->add(new \DateInterval(self::MEMBERSHIP_DURATION));
        $graceDate = $expiryDate->add(new \DateInterval(self::GRACE_PERIOD));

        return new \DateTime() > $graceDate;
    }

    private function getLastMembershipPaymentDate(User $user): ?\DateTime
    {
        $lastMembershipDonation = $this->entityManager->getRepository(Donation::class)
            ->findOneBy(
                ['donor' => $user, 'donationType' => 'membership'],
                ['transactionDate' => 'DESC']
            );

        return $lastMembershipDonation?->getTransactionDate();
    }
}
