<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Event\FlowerCycleCompletedEvent;
use App\Repository\DonationRepository;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FlowerProgressionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FlowerRepository $flowerRepository,
        private readonly DonationRepository $donationRepository,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function checkAndProcessProgression(User $user): void
    {
        $currentFlower = $user->getCurrentFlower();

        if ($this->isFlowerCompleted($user, $currentFlower)) {
            $this->processFlowerCompletion($user, $currentFlower);
        }
    }

    private function isFlowerCompleted(User $user, Flower $flower): bool
    {
        $directDonations = $this->donationRepository->countDirectDonationsInFlower($user, $flower);
        return $directDonations >= 4;
    }

    private function processFlowerCompletion(User $user, Flower $flower): void
    {
        $donations = $this->donationRepository->findFlowerDonations($user, $flower);

        // Calculate total received amount
        $totalAmount = array_reduce($donations, fn($sum, $donation) => $sum + $donation->getAmount(), 0);

        // Split amount (50% to wallet, 50% to solidarity)
        $walletAmount = $totalAmount * 0.5;

        // Begin transaction
        $this->entityManager->beginTransaction();

        try {
            // Update user's wallet
            $user->addToWalletBalance($walletAmount);

            // Process solidarity donation
            $this->processSolidarityDonation($user, $walletAmount);

            // Progress to next flower
            $nextFlower = $this->flowerRepository->findNextFlower($flower);
            if ($nextFlower && !$this->hasReachedCycleLimit($user, $nextFlower)) {
                $user->setCurrentFlower($nextFlower);

                // Place in referrer's structure if applicable
                if ($user->getReferrer()) {
                    $this->placeInReferrerStructure($user, $nextFlower);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Dispatch completion event
            $this->eventDispatcher->dispatch(
                new FlowerCycleCompletedEvent($user, $flower, $nextFlower, $walletAmount)
            );

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function hasReachedCycleLimit(User $user, Flower $flower): bool
    {
        $completedCycles = $this->donationRepository->countCompletedCycles($user, $flower);
        return $completedCycles >= 10;
    }

    private function placeInReferrerStructure(User $user, Flower $flower): void
    {
        $referrer = $user->getReferrer();
        $position = $this->matrixPlacementService->findNextReferralPosition($referrer, $flower);

        if ($position) {
            $donation = new Donation();
            $donation->setDonationType('referral_placement')
                ->setDonor($referrer)
                ->setRecipient($user)
                ->setFlower($flower)
                ->setCyclePosition($position);

            $this->entityManager->persist($donation);
        }
    }

    private function processSolidarityDonation(User $donor, float $amount): void
    {
        $recipient = $this->findSolidarityRecipient();
        if (!$recipient) {
            return;
        }

        $donation = new Donation();
        $donation->setDonationType('solidarity')
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->entityManager->persist($donation);
    }

    private function findSolidarityRecipient(): ?User
    {
        // Implement logic to find the most suitable recipient
        // Could be random, oldest waiting, or based on specific criteria
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['currentFlower' => $this->flowerRepository->findOneBy(['name' => 'Violette'])]);
    }
}
