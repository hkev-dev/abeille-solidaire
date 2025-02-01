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

    public function canProgressToNextFlower(Donation $donation): bool
    {
//        $currentFlower = $donation->getCurrentFlower();
//        $cycleIterations = $this->getUserCycleIterations($user, $currentFlower);
        
//        return $cycleIterations < self::MAX_CYCLE_ITERATIONS;

        return true;
    }

    public function validateFlowerProgression(Donation $donation): bool
    {
        return $this->canProgressToNextFlower($donation);
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

    public function getNextFlower(Flower $flower): ?Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = :level')
            ->setParameter('level', $flower->getLevel() + 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFirstFlower(): Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }
}
