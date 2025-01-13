<?php

namespace App\Repository;

use App\Entity\PaymentMethod;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    public function findByUserAndType(User $user, string $type): array
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.user = :user')
            ->andWhere('pm.methodType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function findUserDefaultMethod(User $user): ?PaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.user = :user')
            ->andWhere('pm.isDefault = :isDefault')
            ->setParameter('user', $user)
            ->setParameter('isDefault', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function removeUserDefaultMethods(User $user): void
    {
        $this->createQueryBuilder('pm')
            ->update()
            ->set('pm.isDefault', ':isDefault')
            ->where('pm.user = :user')
            ->andWhere('pm.isDefault = :wasDefault')
            ->setParameters([
                'user' => $user,
                'isDefault' => false,
                'wasDefault' => true
            ])
            ->getQuery()
            ->execute();
    }

    public function findUserCryptoAddresses(User $user): array
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.user = :user')
            ->andWhere('pm.methodType = :type')
            ->andWhere('pm.cryptoAddress IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('type', PaymentMethod::TYPE_CRYPTO)
            ->getQuery()
            ->getResult();
    }
}
