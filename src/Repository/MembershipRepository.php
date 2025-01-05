<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Membership;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function findLatestByUser(User $user): ?Membership
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active memberships for a user
     */
    public function findActiveByUser(User $user): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.startDate <= :now')
            ->andWhere('m.endDate >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find memberships expiring within the next n days
     */
    public function findExpiringMemberships(int $daysThreshold = 30): array
    {
        $now = new \DateTime();
        $threshold = (new \DateTime())->modify("+{$daysThreshold} days");

        return $this->createQueryBuilder('m')
            ->andWhere('m.endDate BETWEEN :now AND :threshold')
            ->setParameter('now', $now)
            ->setParameter('threshold', $threshold)
            ->orderBy('m.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
