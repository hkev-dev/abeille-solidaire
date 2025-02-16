<?php

namespace App\Repository;

use App\Entity\Withdrawal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

/**
 * @extends ServiceEntityRepository<Withdrawal>
 */
class WithdrawalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Withdrawal::class);
    }

    public function getTotalWithdrawnInPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $result = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.user = :user')
            ->andWhere('w.requestedAt BETWEEN :startDate AND :endDate')
            ->andWhere('w.status = :status')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', Withdrawal::STATUS_PROCESSED)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }
    
    public function findUserWithdrawals(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.requestedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalAmount()
    {
        return $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->andWhere('w.status = :status')
            ->setParameter('status', Withdrawal::STATUS_PROCESSED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalPendingAmount()
    {
        return $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->andWhere('w.status = :status')
            ->setParameter('status', Withdrawal::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getGraphData()
    {
        return $this->createQueryBuilder('w')
            ->select('SUM(w.amount) as totalAmount, YEAR(w.requestedAt) as year, MONTH(w.requestedAt) as month')
            ->where('w.status = :status')
            ->setParameter('status', Withdrawal::STATUS_PROCESSED)
            ->groupBy('year, month')
            ->orderBy('year, month')
            ->getQuery()
            ->getResult();
    }
}
