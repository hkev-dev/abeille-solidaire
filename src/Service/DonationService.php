<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\ORM\EntityManagerInterface;

class DonationService
{
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
        ?Flower $flower = null
    ): Donation {
        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setDonationType($donationType)
            ->setFlower($flower)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->em->persist($donation);
        $this->em->flush();

        return $donation;
    }

    public function createSolidarityDonation(User $donor, float $amount, ?Flower $flower = null): ?Donation
    {
        // Find an active user who was recruited by others but hasn't recruited yet
        // These users need the most support to grow their matrix
        $recipient = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->leftJoin('u.children', 'c')
            ->where('u.id != :userId')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->andWhere('u.parent IS NOT NULL')  // Must have a parent (recruited)
            ->andWhere('COUNT(c.id) = 0')       // Has no children yet (needs help)
            ->setParameter('userId', $donor->getId())
            ->setParameter('status', 'completed')
            ->groupBy('u.id')
            ->orderBy('u.createdAt', 'ASC')  // Prioritize longest waiting
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($recipient) {
            return $this->createDonation(
                $donor,
                $recipient,
                $amount,
                Donation::TYPE_SOLIDARITY,
                $flower
            );
        }

        return null;
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