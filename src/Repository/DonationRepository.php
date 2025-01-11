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

    public function getReferrerMatrixPositions(User $referrer, Flower $flower): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donor = :referrer')
            ->setParameter('flower', $flower)
            ->setParameter('referrer', $referrer)
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('types', ['direct', 'registration', 'referral_placement']);

        $result = $qb->getQuery()->getResult();

        $positions = [];
        foreach ($result as $row) {
            $positions[$row['cyclePosition']] = $row['recipient_id'];
        }

        return $positions;
    }

    public function findByFlowerMatrix(Flower $flower): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration'])
            ->orderBy('d.cyclePosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByReferrerMatrix(User $referrer, Flower $flower): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donor = :referrer')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('referrer', $referrer)
            ->setParameter('types', ['direct', 'registration', 'referral_placement']);

        $result = $qb->getQuery()->getResult();

        $positions = [];
        foreach ($result as $row) {
            $positions[$row['cyclePosition']] = $row['recipient_id'];
        }

        return $positions;
    }

    public function getTotalReceivedByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getTotalMadeByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getCurrentFlowerProgress(User $user): array
    {
        $qb = $this->createQueryBuilder('d');
        $received = $qb->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'received' => (int) $received,
            'total' => 4,
            'percentage' => ($received / 4) * 100
        ];
    }

    /**
     * @return Donation[]
     */
    public function findRecentByUser(User $user, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
