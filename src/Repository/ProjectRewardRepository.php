<?php

namespace App\Repository;

use App\Entity\ProjectReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProjectReward|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReward|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReward[]    findAll()
 * @method ProjectReward[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReward::class);
    }

}
