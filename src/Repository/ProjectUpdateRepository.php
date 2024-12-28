<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ProjectUpdate;

/**
 * @method ProjectUpdate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectUpdate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectUpdate[]    findAll()
 * @method ProjectUpdate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectUpdate::class);
    }

    public function findLatestByProject($project, int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.project = :project')
            ->setParameter('project', $project)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange($project, \DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.project = :project')
            ->andWhere('u.createdAt BETWEEN :start AND :end')
            ->setParameter('project', $project)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUpdateStats($project): array
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as total')
            ->addSelect('MAX(u.createdAt) as lastUpdate')
            ->addSelect('MIN(u.createdAt) as firstUpdate')
            ->where('u.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleResult();
    }
}
