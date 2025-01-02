<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByReferralCode(string $referralCode): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.referralCode = :code')
            ->setParameter('code', $referralCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersInFlowerWithSpace(Flower $flower): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.donationsReceived', 'd')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = :verified')
            ->groupBy('u.id')
            ->having('COUNT(d.id) < 4')
            ->setParameter('flower', $flower)
            ->setParameter('verified', true)
            ->getQuery()
            ->getResult();
    }

    public function findDirectReferrals(User $referrer): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.referrer = :referrer')
            ->setParameter('referrer', $referrer)
            ->getQuery()
            ->getResult();
    }

    public function getTotalWalletBalance(): float
    {
        $result = $this->createQueryBuilder('u')
            ->select('SUM(u.walletBalance)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findExpiredWaitingUsers(\DateTime $threshold): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.registrationPaymentStatus = :status')
            ->andWhere('u.waitingSince < :threshold')
            ->setParameter('status', 'pending')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function findPendingRegistrations(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'pending')
            ->orderBy('u.waitingSince', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiredWaitingRoomUsers(\DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.registrationPaymentStatus = :status')
            ->andWhere('u.waitingSince < :threshold')
            ->setParameter('status', 'pending')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function findNextRecipientInFlower(Flower $flower): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->andSelect(
                '(SELECT COUNT(d) FROM App:Donation d 
                WHERE d.recipient = u AND d.flower = :flower) as donation_count'
            )
            ->having('donation_count < 4')
            ->setParameter('flower', $flower)
            ->setParameter('status', 'completed')
            ->orderBy('u.waitingSince', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersReadyForFlowerUpgrade(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.donationsReceived', 'd')
            ->where('d.flower = u.currentFlower')
            ->groupBy('u.id')
            ->having('COUNT(d.id) >= 4')
            ->getQuery()
            ->getResult();
    }

    public function findPotentialSolidarityRecipients(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->andWhere('u.projectDescription IS NOT NULL')
            ->orderBy('u.walletBalance', 'ASC')
            ->setParameter('status', 'completed')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function countActiveUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findInactiveAccounts(\DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.registrationPaymentStatus = :status')
            ->andWhere('u.waitingSince < :threshold')
            ->andWhere('u.isVerified = false')
            ->setParameter('status', 'pending')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function getUsersAwaitingDonationsInFlower(Flower $flower): array
    {
        $qb = $this->createQueryBuilder('u');
        
        return $qb->select('u', 'COUNT(d.id) as donation_count')
            ->leftJoin('u.donationsReceived', 'd', 'WITH', 'd.flower = :flower')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = true')
            ->groupBy('u.id')
            ->having('donation_count < 4')
            ->setParameter('flower', $flower)
            ->orderBy('u.waitingSince', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
