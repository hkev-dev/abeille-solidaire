<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MembershipService
{
    private const MEMBERSHIP_FEE = 25.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationService $donationService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createInitialMembership(User $user, Donation $payment): Membership
    {
        $membership = new Membership();
        $membership->setUser($user)
            ->setPayment($payment);

        $membership->activate();

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $membership;
    }

    public function renewMembership(User $user, Donation $payment): Membership
    {
        $currentMembership = $user->getCurrentMembership();
        
        if ($currentMembership) {
            $currentMembership->renew($payment);
            $this->entityManager->flush();
            return $currentMembership;
        }

        return $this->createInitialMembership($user, $payment);
    }

    public function isExpired(User $user): bool
    {
        $currentMembership = $user->getCurrentMembership();
        return !$currentMembership || $currentMembership->isExpired();
    }

    public function getLatestMembership(User $user): ?Membership
    {
        return $user->getLastMembership();
    }

    public function getRenewalAmount(): float
    {
        return self::MEMBERSHIP_FEE;
    }

    public function getMembershipHistory(User $user): array
    {
        return $user->getMemberships()->toArray();
    }
}
