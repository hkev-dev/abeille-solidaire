<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method findOneBySlug(string $slug)
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findActiveOrderByReceivedAmount(?int $limit = null): array
    {
         $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.creator', 'creator')
            ->leftJoin('creator.donationsMade', 'donations')
            ->leftJoin('donations.earnings', 'earnings')
            ->andWhere('p.isActive = :active')
            ->addSelect('COALESCE(SUM(earnings.amount), 0) AS HIDDEN receivedAmount')
            ->setParameter('active', true)
            ->groupBy('p.id')
            ->orderBy('receivedAmount', 'DESC');

         if ($limit){
             $qb->setMaxResults($limit);
         }

           return $qb->getQuery()
            ->getResult();
    }
}
