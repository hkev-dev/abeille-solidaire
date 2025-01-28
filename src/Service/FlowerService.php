<?php

namespace App\Service;

use App\Entity\Flower;
use App\Entity\User;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlowerService
{
    public const MAX_CYCLE_ITERATIONS = 10;

    protected EntityManagerInterface $em;
    protected EventDispatcherInterface $eventDispatcher;
    protected MembershipService $membershipService;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        MembershipService $membershipService
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->membershipService = $membershipService;
    }

    public function getUserCycleIterations(User $user, Flower $flower): int
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Donation::class, 'd')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->andWhere('d.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function canProgressToNextFlower(User $user): bool
    {
        $currentFlower = $user->getCurrentFlower();
        $cycleIterations = $this->getUserCycleIterations($user, $currentFlower);
        
        return $cycleIterations < self::MAX_CYCLE_ITERATIONS;
    }

    public function validateFlowerProgression(User $user): bool
    {
        if (!$user->isKycVerified()) {
            return false;
        }

        if (!$this->membershipService->canProgressInFlowers($user)) {
            return false;
        }

        return $this->canProgressToNextFlower($user);
    }

    public function getCurrentCycleCount(User $user): int
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Donation::class, 'd')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->andWhere('d.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasReachedCycleLimit(User $user): bool
    {
        $cycleCount = $this->getCurrentCycleCount($user);
        return $cycleCount >= 10;
    }

    public function getNextFlower(Flower $current): ?Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = :level')
            ->setParameter('level', $current->getLevel() + 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
