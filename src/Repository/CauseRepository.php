<?php

namespace App\Repository;

use App\Entity\Cause;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cause>
 */
class CauseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cause::class);
    }

    public function save(Cause $cause, bool $andFlush = false): void
    {
        $this->getEntityManager()->persist($cause);

        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }
}
