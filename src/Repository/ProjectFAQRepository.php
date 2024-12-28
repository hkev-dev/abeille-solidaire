<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ProjectFAQ;

class ProjectFAQRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFAQ::class);
    }

    public function findActiveByProject($project): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.project = :project')
            ->orderBy('f.createdAt', 'DESC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    public function findByPosition($project): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.project = :project')
            ->orderBy('f.position', 'ASC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    public function searchInProject($project, string $query): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.project = :project')
            ->andWhere('f.question LIKE :query OR f.answer LIKE :query')
            ->setParameter('project', $project)
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
