<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ProjectReward;

/**
 * @method ProjectReward|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReward|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReward[]    findAll()
 * @method ProjectReward[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReward::class);
    }

    public function findActiveByProject($project): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.project = :project')
            ->andWhere('r.estimatedDelivery > :now')
            ->setParameter('project', $project)
            ->setParameter('now', new \DateTime())
            ->orderBy('r.amount', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPopularRewards(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.backerCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByPriceRange($project, float $min, float $max): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.project = :project')
            ->andWhere('r.amount BETWEEN :min AND :max')
            ->setParameter('project', $project)
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->orderBy('r.amount', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
