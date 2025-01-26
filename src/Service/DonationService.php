<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\ORM\EntityManagerInterface;

class DonationService
{
    public const MEMBERSHIP_FEE = 25.00;

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createDonation(
        User $donor,
        User $recipient,
        float $amount,
        string $donationType,
        ?Flower $flower = null,
        string $paymentStatus = 'pending'
    ): Donation {
        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setDonationType($donationType)
            ->setFlower($flower)
            ->setPaymentStatus($paymentStatus)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->em->persist($donation);
        $this->em->flush();

        return $donation;
    }

    public function createSolidarityDonation(User $donor, float $amount, ?Flower $flower = null): ?Donation
    {
        // Find root user (Abeille Solidaire - matrixDepth = 0)
        $rootUser = $this->em->getRepository(User::class)
            ->findOneBy(['matrixDepth' => 0]);

        if (!$rootUser) {
            throw new \RuntimeException('Root user (Abeille Solidaire) not found');
        }

        $donation = $this->createDonation(
            $donor,
            $rootUser,
            $amount,
            Donation::TYPE_SOLIDARITY,
            $flower,
            'completed' // Set payment status as completed immediately
        );

        // Set payment provider as internal since this is an automatic system transfer
        $donation->setPaymentProvider('internal');
        $this->em->flush();

        return $donation;
    }

    public function createMembershipDonation(User $donor): ?Donation
    {
        // Find root user (matrixDepth = 0)
        $rootUser = $this->em->getRepository(className: User::class)
            ->findOneBy(['matrixDepth' => 0]);

        if (!$rootUser) {
            throw new \RuntimeException('Root user not found');
        }

        return $this->createDonation(
            $donor,
            $rootUser,
            self::MEMBERSHIP_FEE,
            Donation::TYPE_MEMBERSHIP,
            $donor->getCurrentFlower()
        );
    }

    public function hasCompletedCycle(User $user): bool
    {
        $donations = $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Donation::class, 'd')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->getQuery()
            ->getSingleScalarResult();

        return $donations >= 4;
    }
}