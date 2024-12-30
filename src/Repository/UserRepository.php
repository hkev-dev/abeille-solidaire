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
}
