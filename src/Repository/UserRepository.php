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

    public function countActiveMembers(): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        return (int) $result ?? 0;
    }

    public function getMatrixPositionsForFlower(Flower $flower): array
    {
        return $this->createQueryBuilder('u')
            ->select([
                'u.id as user_id',
                'u.firstName as first_name',
                'u.lastName as last_name',
                'u.matrixPosition as matrix_position',
                'u.matrixDepth as matrix_depth',
                'IDENTITY(u.parent) as parent_id'
            ])
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->setParameter('flower', $flower)
            ->setParameter('status', 'completed')
            ->orderBy('u.matrixDepth', 'ASC')
            ->addOrderBy('u.matrixPosition', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
