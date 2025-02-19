<?php

namespace App\Repository;

use App\Constant\Enum\Project\State;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method findOneBySlug(string $slug)
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findActiveOrderByReceivedAmount(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.creator', 'creator')
            ->leftJoin('creator.donationsMade', 'donations')
            ->leftJoin('donations.earnings', 'earnings')
            ->andWhere('p.isActive = :active')
            ->addSelect('COALESCE(SUM(earnings.amount), 0) AS HIDDEN receivedAmount')
            ->setParameter('active', true)
            ->groupBy('p.id')
            ->orderBy('receivedAmount', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function save(Project $project, bool $andFlush = false): void
    {
        $this->getEntityManager()->persist($project);

        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $project, bool $andFlush = false): void
    {
        $this->getEntityManager()->remove($project);

        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param User $user
     * @return Project[]
     */
    public function findCompletedByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('project')
            ->andWhere('project.owner = :user OR project.creator = :user')
            ->andWhere('project.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', State::COMPLETED);
        return $qb->getQuery()
            ->getResult();
    }
}
