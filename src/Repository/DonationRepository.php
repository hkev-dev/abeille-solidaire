<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function findUserDonationsInFlower(User $user, Flower $flower): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', [Donation::TYPE_DIRECT, Donation::TYPE_SOLIDARITY])
            ->orderBy('d.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countDonationsInCycle(User $user, Flower $flower): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', [Donation::TYPE_DIRECT, Donation::TYPE_SOLIDARITY])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPendingDonations(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.stripePaymentIntentId IS NOT NULL')
            ->andWhere('d.transactionDate >= :date')
            ->setParameter('date', new \DateTime('-24 hours'))
            ->getQuery()
            ->getResult();
    }
}
