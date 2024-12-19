<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findUpcoming()
    {
        return $this->createQueryBuilder('e')
            ->join('e.details', 'd')
            ->where('d.startDate >= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('d.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
