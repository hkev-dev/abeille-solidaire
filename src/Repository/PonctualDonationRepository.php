<?php

namespace App\Repository;

use App\Entity\PonctualDonation;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PonctualDonation>
 */
class PonctualDonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PonctualDonation::class);
    }

    public function findRecent()
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->andWhere('d.isPaid = :paid')
            ->setParameter('paid', true)
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
