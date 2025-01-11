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

    public function findPendingWithdrawals(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', Withdrawal::STATUS_PENDING)
            ->orderBy('w.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUserWithdrawalsInPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.requestedAt BETWEEN :startDate AND :endDate')
            ->andWhere('w.status = :status')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', Withdrawal::STATUS_PROCESSED)
            ->getQuery()
            ->getResult();
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

    public function findUserWithdrawalsWithFilters(
        User $user,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $status = null,
        ?string $method = null,
        ?string $search = null
    ): array {
        $qb = $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.requestedAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($status) {
            $qb->andWhere('w.status = :status')
                ->setParameter('status', $status);
        }

        if ($method) {
            $qb->andWhere('w.withdrawalMethod = :method')
                ->setParameter('method', $method);
        }

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('w.stripePayoutId', ':search'),
                    $qb->expr()->like('w.coinpaymentsWithdrawalId', ':search'),
                    $qb->expr()->like('w.cryptoCurrency', ':search'),
                    $qb->expr()->like('CAST(w.amount AS STRING)', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('w.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
