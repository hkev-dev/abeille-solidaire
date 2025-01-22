<?php

namespace App\Service;

use App\Entity\Flower;
use Doctrine\ORM\EntityManagerInterface;

class FlowerService
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getNextFlower(Flower $current): ?Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = :level')
            ->setParameter('level', $current->getLevel() + 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}