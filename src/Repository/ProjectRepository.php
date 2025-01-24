<?php

namespace App\Repository;

use App\Entity\Flower;
use App\Entity\Project;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method findOneBySlug(string $slug)
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
            ->where('p.isActive = :active')
            ->setParameter('now', new \DateTime())
            ->setParameter('active', true)
            ->orderBy('p.pledged', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
