<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findSimilarProjects(Project $project, int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.id != :projectId')
            ->setParameter('category', $project->getCategory())
            ->setParameter('projectId', $project->getId())
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
