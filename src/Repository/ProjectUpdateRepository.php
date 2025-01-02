<?php

namespace App\Repository;

use App\Entity\ProjectUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProjectUpdate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectUpdate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectUpdate[]    findAll()
 * @method ProjectUpdate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectUpdate::class);
    }
}
