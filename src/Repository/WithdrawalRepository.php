<?php

namespace App\Repository;

use App\Entity\Withdrawal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
