<?php

namespace App\Repository;

use App\Entity\Flower;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FlowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flower::class);
    }

    public function findNextFlower(Flower $currentFlower): ?Flower
    {
        return $this->createQueryBuilder('f')
            ->where('f.level > :currentLevel')
            ->setParameter('currentLevel', $currentFlower->getLevel())
            ->orderBy('f.level', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersInFlower(Flower $flower): array
    {
        return $this->createQueryBuilder('f')
            ->select('u')
            ->join('f.currentUsers', 'u')
            ->where('f = :flower')
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getResult();
    }

    public function getMatrixPositions(Flower $flower): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return $qb->select('d.cyclePosition, IDENTITY(d.recipient) as recipient_id')
            ->from('App:Donation', 'd')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getQuery()
            ->getResult();
    }

    public function getAvailablePositionsForReferrer(User $referrer, Flower $flower): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $usedPositions = $qb->select('d.cyclePosition')
            ->from('App:Donation', 'd')
            ->where('d.flower = :flower')
            ->andWhere('d.donor = :referrer')
            ->setParameter('flower', $flower)
            ->setParameter('referrer', $referrer)
            ->getQuery()
            ->getArrayResult();

        // Convert to simple array
        $usedPositions = array_column($usedPositions, 'cyclePosition');
        
        // Generate available positions (1-16)
        $allPositions = range(1, 16);
        return array_values(array_diff($allPositions, $usedPositions));
    }

    public function getNextFlower(Flower $currentFlower): ?Flower
    {
        return $this->createQueryBuilder('f')
            ->where('f.donationAmount > :currentAmount')
            ->setParameter('currentAmount', $currentFlower->getDonationAmount())
            ->orderBy('f.donationAmount', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFlowerCompletionStats(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return $qb->select('f.name', 'f.donationAmount', 'COUNT(d.id) as completion_count')
            ->from('App:FlowerCycleCompletion', 'fcc')
            ->join('fcc.flower', 'f')
            ->leftJoin('App:Donation', 'd', 'WITH', 'd.flower = f AND d.recipient = :user')
            ->where('fcc.user = :user')
            ->setParameter('user', $user)
            ->groupBy('f.id')
            ->getQuery()
            ->getResult();
    }

    public function countUserCyclesInFlower(User $user, Flower $flower): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(DISTINCT d.id) as cycles')
            ->join('App:Donation', 'd', 'WITH', 'd.flower = f')
            ->where('f = :flower')
            ->andWhere('d.recipient = :user')
            ->andWhere('d.donationType = :type')
            ->setParameter('flower', $flower)
            ->setParameter('user', $user)
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getActiveUsersInFlower(Flower $flower): array
    {
        return $this->createQueryBuilder('f')
            ->select('u')
            ->join('App:User', 'u', 'WITH', 'u.currentFlower = f')
            ->where('f = :flower')
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getResult();
    }
}
