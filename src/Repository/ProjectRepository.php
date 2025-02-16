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

    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.creator', 'u')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.receivedAmount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
