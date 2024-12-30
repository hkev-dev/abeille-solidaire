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
            ->andWhere('p.endDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.pledged', 'DESC')  // Order by most funded
            ->getQuery()
            ->getResult();
    }

    public function findEligibleForSolidarityDonation(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.isActive = :active')
            ->andWhere('u.isVerified = :verified')
            ->orderBy('p.totalReceived', 'ASC')
            ->setParameter('active', true)
            ->setParameter('verified', true)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findByFlowerLevel(Flower $flower): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('u.currentFlower = :flower')
            ->andWhere('p.isActive = :active')
            ->setParameter('flower', $flower)
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageDonationsByFlower(Flower $flower): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('AVG(p.totalReceived)')
            ->join('p.user', 'u')
            ->where('u.currentFlower = :flower')
            ->setParameter('flower', $flower)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }
}
