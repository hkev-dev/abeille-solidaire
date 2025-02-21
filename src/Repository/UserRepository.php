<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Flower;
use DateTime;
use Doctrine\ORM\Query\Expr\Join;
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

    /**
     * Find users with expired registrations (unverified and waiting for more than specified days)
     */
    public function findByExpiredRegistration(DateTime $expiryDate): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isKycVerified = :kyc')
            ->andWhere('u.waitingSince IS NOT NULL')
            ->andWhere('u.waitingSince < :expiryDate')
            ->setParameter('status', 'pending')
            ->setParameter('kyc', false)
            ->setParameter('expiryDate', $expiryDate)
            ->getQuery()
            ->getResult();
    }

    public function countVerified()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isKycVerified = :kyc')
            ->setParameter('kyc', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent()
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.createdAt > :date')
            ->setParameter('date', new DateTime('-3 days'))
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }


    /**
     * @param DateTime $dateThreshold
     * @return User[]
     */
    public function findUnactivatedUsers(DateTime $dateThreshold): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.donationsMade', 'd')
            ->andWhere('u.createdAt < :dateThreshold')
            ->andWhere('u.donationsMade IS EMPTY OR NOT EXISTS (
                SELECT 1 FROM App\Entity\Donation d2 
                WHERE d2.donor = u AND d2.paymentStatus = :completedStatus
            )')
            ->andWhere('CAST(u.roles AS TEXT) NOT LIKE :roleAdmin OR CAST(u.roles AS TEXT) NOT LIKE :roleSuperAdmin')
            ->setParameter('dateThreshold', $dateThreshold)
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->setParameter('roleSuperAdmin', '%ROLE_SUPER_ADMIN%')
            ->setParameter('completedStatus', 'completed');

        return $qb->getQuery()
            ->getResult();
    }
}
