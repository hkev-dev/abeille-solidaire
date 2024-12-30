<?php

namespace App\Repository;

use App\Entity\Flower;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FlowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flower::class);
    }

    public function findNextFlower(Flower $currentFlower): ?Flower
    {
        return $this->createQueryBuilder('f')
            ->where('f.level > :currentLevel')
            ->setParameter('currentLevel', $currentFlower->getLevel())
            ->orderBy('f.level', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersInFlower(Flower $flower): array
    {
        return $this->createQueryBuilder('f')
            ->select('u')
            ->join('f.currentUsers', 'u')
            ->where('f = :flower')
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getResult();
    }
}
