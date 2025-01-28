<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\MembershipExpiredEvent;
use App\Event\MembershipActivatedEvent;

class MembershipService
{
    public const MEMBERSHIP_FEE = 25.0;
    public const EXPIRATION_WARNING_DAYS = [30, 15, 7, 3, 1];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function createMembership(User $user, Donation $payment): Membership
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
            $this->eventDispatcher->dispatch(new MembershipActivatedEvent($currentMembership));
            $this->entityManager->flush();
            return $currentMembership;
        }

        $membership = $this->createMembership($user, $payment);
        $this->eventDispatcher->dispatch(new MembershipActivatedEvent($membership));
        return $membership;
    }

    public function canParticipateInMatrix(User $user): bool
    {
        // Admins can always participate
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles())) {
            return true;
        }

        $currentMembership = $user->getCurrentMembership();
        return $currentMembership && !$currentMembership->isExpired();
    }

    public function canProgressInFlowers(User $user): bool
    {
        return $this->canParticipateInMatrix($user);
    }

    public function checkExpirationWarnings(): array
    {
        $warnings = [];
        $qb = $this->entityManager->createQueryBuilder();
        
        foreach (self::EXPIRATION_WARNING_DAYS as $days) {
            $expiringMemberships = $qb->select('m')
                ->from(Membership::class, 'm')
                ->where('m.status = :status')
                ->andWhere('m.endDate BETWEEN :start AND :end')
                ->setParameter('status', Membership::STATUS_ACTIVE)
                ->setParameter('start', new \DateTime("+{$days} days"))
                ->setParameter('end', new \DateTime("+".($days+1)." days"))
                ->getQuery()
                ->getResult();

            foreach ($expiringMemberships as $membership) {
                $warnings[] = [
                    'user' => $membership->getUser(),
                    'daysUntilExpiration' => $days,
                    'membership' => $membership
                ];
            }
        }

        return $warnings;
    }

    public function checkAndUpdateExpiredMemberships(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $expiredMemberships = $qb->select('m')
            ->from(Membership::class, 'm')
            ->where('m.status = :status')
            ->andWhere('m.endDate < :now')
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        foreach ($expiredMemberships as $membership) {
            $membership->setStatus(Membership::STATUS_EXPIRED);
            $this->eventDispatcher->dispatch(new MembershipExpiredEvent($membership));
            $this->logger->info(sprintf(
                'Membership expired for user %s (ID: %d)',
                $membership->getUser()->getEmail(),
                $membership->getUser()->getId()
            ));
        }

        $this->entityManager->flush();
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
