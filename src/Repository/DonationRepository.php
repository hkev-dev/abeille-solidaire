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
        // Get total completed direct donations for this flower
        $totalDonations = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();

        // Calculate cycles (every 4 donations completes a cycle)
        $completedCycles = (int)($totalDonations / 4);
        $donationsInCurrentCycle = $totalDonations % 4;

        return [
            'totalCompletedCycles' => $completedCycles,
            'currentCycleNumber' => $completedCycles + 1,
            'donationsInCurrentCycle' => $donationsInCurrentCycle,
            'lastCompletedPosition' => $completedCycles * 4
        ];
    }

    public function countByRecipientAndFlower(User $user, ?Flower $flower): int
    {
        if (!$flower) {
            return 0;
        }

        $result = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function countCompletedCycles(User $user, Flower $flower): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('COUNT(d.id) as donationCount')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :donationType')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('donationType', Donation::TYPE_REGISTRATION)
            ->getQuery()
            ->getSingleScalarResult();

        // Each 4 donations completes a cycle
        return (int) floor($result / 4);
    }
}
