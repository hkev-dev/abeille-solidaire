<?php

namespace App\Repository;

use App\Entity\Earning;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Earning>
 */
class EarningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Earning::class);
    }

//    /**
//     * @return Earning[] Returns an array of Earning objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Earning
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findEarned(User $user)
    {
        return $this->createQueryBuilder('e')
            ->select('e')
            ->where('e.beneficiary IN (:donations)')
            ->setParameter('donations', $user->getDonationsMade()->toArray())
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getTotalReceivedByUser(?UserInterface $user)
    {
        return $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.beneficiary IN (:donations)')
            ->setParameter('donations', $user->getDonationsMade()->toArray())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalMadeByUser(?UserInterface $user)
    {
        return $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.donor IN (:donations)')
            ->setParameter('donations', $user->getDonationsMade()->toArray())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalSupMadeByUser(?UserInterface $user)
    {
        return $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->leftJoin('e.donor', 'd')
            ->where('e.donor IN (:donations)')
            ->andWhere('d.donationType = :donationType')
            ->setParameter('donations', $user->getDonationsMade()->toArray())
            ->setParameter('donationType', 'supplementary')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
