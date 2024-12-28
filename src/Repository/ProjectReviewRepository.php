<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ProjectReview;

/**
 * @method ProjectReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReview[]    findAll()
 * @method ProjectReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReview::class);
    }

    public function getProjectStats($project): array
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as total')
            ->addSelect('AVG(r.rating) as average')
            ->addSelect('COUNT(CASE WHEN r.rating = 5 THEN 1 END) as fiveStars')
            ->addSelect('COUNT(CASE WHEN r.rating = 4 THEN 1 END) as fourStars')
            ->addSelect('COUNT(CASE WHEN r.rating = 3 THEN 1 END) as threeStars')
            ->addSelect('COUNT(CASE WHEN r.rating = 2 THEN 1 END) as twoStars')
            ->addSelect('COUNT(CASE WHEN r.rating = 1 THEN 1 END) as oneStar')
            ->where('r.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleResult();
    }

    public function findLatestByProject($project, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.project = :project')
            ->orderBy('r.createdAt', 'DESC')
            ->setParameter('project', $project)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByRating($project, int $rating): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.project = :project')
            ->andWhere('r.rating = :rating')
            ->setParameter('project', $project)
            ->setParameter('rating', $rating)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
