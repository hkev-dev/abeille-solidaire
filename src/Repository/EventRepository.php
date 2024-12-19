<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

class EventRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Event::class);
        $this->paginator = $paginator;
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

    public function findPaginatedEvents(int $page = 1, int $limit = 6)
    {
        $query = $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery();

        return $this->paginator->paginate(
            $query,
            $page,
            $limit
        );
    }
}
