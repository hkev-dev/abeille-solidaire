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

    /**
     * Find users with expired registrations (unverified and waiting for more than specified days)
     */
    public function findByExpiredRegistration(\DateTime $expiryDate): array
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
}
