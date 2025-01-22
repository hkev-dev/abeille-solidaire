<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function getTotalReceivedByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getTotalMadeByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getCurrentFlowerProgress(User $user): array
    {
        $qb = $this->createQueryBuilder('d');
        $received = $qb->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'received' => (int) $received,
            'total' => 4,
            'percentage' => ($received / 4) * 100
        ];
    }

    /**
     * @return Donation[]
     */
    public function findRecentByUser(User $user, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByFlowerAndRecipient(Flower $flower, User $recipient, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.flower = :flower')
            ->andWhere('d.recipient = :recipient')
            ->setParameter('flower', $flower)
            ->setParameter('recipient', $recipient)
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalReceivedInFlower(User $user, Flower $flower): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.flower = :flower')
            ->andWhere('d.recipient = :recipient')
            ->setParameter('flower', $flower)
            ->setParameter('recipient', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findByFlowerWithActivity(Flower $flower, int $limit): array
    {
        $donations = $this->createQueryBuilder('d')
            ->select('d', 'donor', 'recipient')
            ->join('d.donor', 'donor')
            ->join('d.recipient', 'recipient')
            ->where('d.flower = :flower')
            ->setParameter('flower', $flower)
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Transform donations into activity items
        return array_map(function($donation) {
            return [
                'date' => $donation->getTransactionDate(),
                'amount' => $donation->getAmount()
            ];
        }, $donations);
    }

    public function findTotalMadeInFlower(User $user, ?Flower $flower): float
    {
        if (!$flower) {
            return 0.0;
        }

        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->andWhere('d.flower = :flower')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findTotalSolidarityReceived(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findTotalSolidarityDistributed(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getCurrentCycleInfo(User $user, Flower $flower): array
    {
        $qb = $this->createQueryBuilder('d');
        
        // Get total completed cycles
        $completedCycles = $this->createQueryBuilder('d1')
            ->select('COUNT(DISTINCT d1.cyclePosition) / 4')
            ->where('d1.recipient = :user')
            ->andWhere('d1.flower = :flower')
            ->getQuery()
            ->getSingleScalarResult();

        // Get current cycle donations
        $currentCycleDonations = $qb
            ->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->andWhere('d.cyclePosition > :lastCompletedPosition')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('lastCompletedPosition', $completedCycles * 4)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalCompletedCycles' => (int)$completedCycles,
            'currentCycleNumber' => (int)$completedCycles + 1,
            'donationsInCurrentCycle' => (int)$currentCycleDonations,
            'lastCompletedPosition' => $completedCycles * 4
        ];
    }

    public function findCurrentCycleDonations(User $user, Flower $flower): array
    {
        $cycleInfo = $this->getCurrentCycleInfo($user, $flower);
        
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.cyclePosition > :lastCompleted')
            ->andWhere('d.cyclePosition <= :currentMax')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('lastCompleted', $cycleInfo['lastCompletedPosition'])
            ->setParameter('currentMax', $cycleInfo['lastCompletedPosition'] + 4)
            ->orderBy('d.cyclePosition', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
