<?php

namespace App\Repository;

use App\Entity\ProjectBacking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectBackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectBacking::class);
    }

    public function findByProject($projectId)
    {
        return $this->createQueryBuilder('pb')
            ->andWhere('pb.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('pb.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser($userId)
    {
        return $this->createQueryBuilder('pb')
            ->andWhere('pb.backer = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('pb.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
