<?php

namespace App\Repository;

use App\Entity\QueuedReferralPlacement;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QueuedReferralPlacementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueuedReferralPlacement::class);
    }

    public function findQueuedReferrals(User $referrer, Flower $flower): array
    {
        return $this->createQueryBuilder('qrp')
            ->join('qrp.referral', 'r')
            ->where('r.referrer = :referrer')
            ->andWhere('qrp.flower = :flower')
            ->setParameter('referrer', $referrer)
            ->setParameter('flower', $flower)
            ->orderBy('qrp.queuedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
