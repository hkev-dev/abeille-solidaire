<?php

namespace App\Repository;

use App\Entity\ProjectReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

}
