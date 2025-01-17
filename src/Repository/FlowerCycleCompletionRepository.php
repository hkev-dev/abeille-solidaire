<?php

namespace App\Repository;

use App\Entity\FlowerCycleCompletion;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FlowerCycleCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FlowerCycleCompletion::class);
    }

    public function findUserCompletionsInFlower(User $user, Flower $flower): array
    {
        return $this->createQueryBuilder('fc')
            ->where('fc.user = :user')
            ->andWhere('fc.flower = :flower')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->orderBy('fc.cycleNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countUserCompletionsInFlower(User $user, Flower $flower): int
    {
        return $this->createQueryBuilder('fc')
            ->select('COUNT(fc.id)')
            ->where('fc.user = :user')
            ->andWhere('fc.flower = :flower')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLatestCompletion(User $user, Flower $flower): ?FlowerCycleCompletion
    {
        return $this->createQueryBuilder('fc')
            ->where('fc.user = :user')
            ->andWhere('fc.flower = :flower')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->orderBy('fc.completedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
