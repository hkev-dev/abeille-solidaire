<?php

namespace App\Repository;

use App\Entity\FAQ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FAQRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FAQ::class);
    }

    public function findActiveFAQs(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchFAQs(string $query): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.isActive = :active')
            ->andWhere('LOWER(f.question) LIKE LOWER(:query) OR LOWER(f.answer) LIKE LOWER(:query)')
            ->setParameter('active', true)
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
