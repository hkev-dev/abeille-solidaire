<?php

namespace App\Repository;

use App\Entity\ProjectStory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectStory>
 *
 * @method ProjectStory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectStory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectStory[]    findAll()
 * @method ProjectStory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectStoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectStory::class);
    }

    public function save(ProjectStory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProjectStory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByProject($projectId): ?ProjectStory
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
