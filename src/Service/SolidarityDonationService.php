<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SolidarityDonationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processSolidarityDonation(User $donor, float $amount, Flower $flower): Donation
    {
        $recipient = $this->matrixPlacementService->findSolidarityRecipient($flower);
        if (!$recipient) {
            throw new \RuntimeException('No eligible recipient found for solidarity donation');
        }

        $donation = new Donation();
        $donation->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setDonationType('solidarity')
            ->setFlower($flower)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->entityManager->persist($donation);
        $this->entityManager->flush();

        $this->logger->info('Solidarity donation processed', [
            'donor_id' => $donor->getId(),
            'recipient_id' => $recipient->getId(),
            'amount' => $amount,
            'flower' => $flower->getName()
        ]);

        return $donation;
    }
}
