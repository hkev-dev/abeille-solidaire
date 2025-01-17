<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Repository\FlowerRepository;
use App\Entity\FlowerCycleCompletion;
use App\Repository\DonationRepository;
use App\Event\FlowerCycleCompletedEvent;
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
        // Get current cycle position and count for the user
        $currentCycle = $this->getCurrentCycleInfo($user, $flower);
        
        // Check cycle limit (10 iterations per flower)
        if ($currentCycle['totalCompletedCycles'] >= 10) {
            return false;
        }

        // Check if current matrix positions are filled (4 positions per cycle)
        return $currentCycle['donationsInCurrentCycle'] >= 4;
    }

    private function getCurrentCycleInfo(User $user, Flower $flower): array
    {
        return $this->donationRepository->getCurrentCycleInfo($user, $flower);
    }

    private function processFlowerCompletion(User $user, Flower $flower): void
    {
        $this->entityManager->beginTransaction();

        try {
            // Get current cycle information
            $cycleInfo = $this->getCurrentCycleInfo($user, $flower);
            
            if ($cycleInfo['totalCompletedCycles'] >= 10) {
                throw new \RuntimeException('Maximum cycle limit reached for this flower');
            }

            // Process donations for the current cycle only
            $cycleDonations = $this->donationRepository->findCurrentCycleDonations($user, $flower);
            $totalAmount = array_reduce($cycleDonations, fn($sum, $donation) => $sum + $donation->getAmount(), 0);
            
            // Split amount (50% to wallet, 50% to solidarity)
            $walletAmount = $totalAmount * 0.5;
            $solidarityAmount = $totalAmount * 0.5;

            // Update user's wallet
            $user->addToWalletBalance($walletAmount);

            // Process solidarity donation
            $this->processSolidarityDonation($user, $solidarityAmount);

            // Record cycle completion
            $this->recordCycleCompletion($user, $flower, $cycleInfo['currentCycleNumber']);

            // Progress to next flower if all cycles are completed
            if ($cycleInfo['totalCompletedCycles'] + 1 >= 10) {
                $this->progressToNextFlower($user, $flower);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Dispatch completion event
            $this->eventDispatcher->dispatch(
                new FlowerCycleCompletedEvent($user, $flower, $cycleInfo['currentCycleNumber'], $walletAmount)
            );

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function recordCycleCompletion(User $user, Flower $flower, int $cycleNumber): void
    {
        // Get current cycle donations to calculate amounts
        $cycleDonations = $this->donationRepository->findCurrentCycleDonations($user, $flower);
        $totalAmount = array_reduce(
            $cycleDonations, 
            fn($sum, $donation) => $sum + $donation->getAmount(), 
            0
        );

        // Create completion record
        $completion = new FlowerCycleCompletion();
        $completion
            ->setUser($user)
            ->setFlower($flower)
            ->setCycleNumber($cycleNumber)
            ->setCompletedAt(new \DateTimeImmutable())
            ->setTotalAmount($totalAmount)
            ->setCyclePositions(
                array_map(
                    fn($donation) => [
                        'position' => $donation->getCyclePosition(),
                        'donor_id' => $donation->getDonor()->getId(),
                        'amount' => $donation->getAmount()
                    ],
                    $cycleDonations
                )
            );

        $this->entityManager->persist($completion);
    }

    private function progressToNextFlower(User $user, Flower $flower): void
    {
        $nextFlower = $this->flowerRepository->findNextFlower($flower);
        if (!$nextFlower) {
            return;
        }

        $user->setCurrentFlower($nextFlower);

        // Place in referrer's structure if applicable
        if ($user->getReferrer()) {
            $position = $this->matrixPlacementService->findNextReferralPosition(
                $user->getReferrer(), 
                $nextFlower
            );
            
            if ($position) {
                $this->createReferralPlacementDonation($user, $nextFlower, $position);
            }
        }
    }

    private function createReferralPlacementDonation(User $user, Flower $flower, int $position): void
    {
        $donation = new Donation();
        $donation
            ->setDonationType('referral_placement')
            ->setDonor($user->getReferrer())
            ->setRecipient($user)
            ->setFlower($flower)
            ->setCyclePosition($position)
            ->setAmount($flower->getDonationAmount())
            ->setTransactionDate(new \DateTimeImmutable());

        $this->entityManager->persist($donation);
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

    public function getCurrentPosition(User $user): ?int
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return null;
        }

        $result = $this->donationRepository->findUserPositionInFlower($user, $currentFlower);
        
        if (!$result || !isset($result['cycle_position'])) {
            return null;
        }

        return (int) $result['cycle_position'];
    }

    public function getTotalReceivedInCurrentFlower(User $user): float
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return 0.0;
        }

        return $this->donationRepository->calculateTotalReceivedInFlower($user, $currentFlower);
    }

    public function getAllCompletedCycles(User $user): array
    {
        return array_map(
            function ($cycle) {
                return [
                    'flower' => $cycle['flower'],
                    'cycleNumber' => $cycle['cycle_number'],
                    'completedAt' => $cycle['completed_at'],
                    'earned' => $cycle['earned_amount'],
                    'solidarityAmount' => $cycle['solidarity_amount'],
                    'solidarityRecipient' => $cycle['solidarity_recipient']
                ];
            },
            $this->donationRepository->findAllCompletedCycles($user)
        );
    }

    public function getTotalEarned(User $user): float
    {
        return $this->donationRepository->calculateTotalEarned($user);
    }

    public function getNextFlowerRequirements(User $user): array
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return [];
        }

        $progress = $user->getFlowerProgress();
        $requirements = [];

        // Requirement 1: Complete current flower cycle
        $requirements[] = [
            'label' => 'Compléter le cycle actuel',
            'description' => sprintf('Recevoir %d dons supplémentaires dans %s', 
                4 - $progress['received'], 
                $currentFlower->getName()
            ),
            'fulfilled' => $progress['received'] >= 4
        ];

        // Requirement 2: Have active referrals
        $activeReferrals = count($user->getReferrals());
        $requirements[] = [
            'label' => 'Avoir des filleuls actifs',
            'description' => sprintf('Vous avez %d/4 filleuls actifs', $activeReferrals),
            'fulfilled' => $activeReferrals >= 1
        ];

        // Requirement 3: Valid KYC
        $requirements[] = [
            'label' => 'Vérification KYC valide',
            'description' => $user->isKycVerified() ? 
                'KYC validé le ' . $user->getKycVerifiedAt()?->format('d/m/Y') : 
                'La vérification KYC est requise',
            'fulfilled' => $user->isKycVerified()
        ];

        // Requirement 4: Project description
        $requirements[] = [
            'label' => 'Description du projet',
            'description' => $user->getProjectDescription() ? 
                'Description du projet complétée' : 
                'Une description de projet est requise',
            'fulfilled' => !empty($user->getProjectDescription())
        ];

        return $requirements;
    }

    public function getReferralsInNextFlower(User $user, ?Flower $nextFlower): array
    {
        if (!$nextFlower) {
            return [];
        }

        return array_map(
            function ($referral) use ($nextFlower) {
                return [
                    'user' => $referral['user'],
                    'position' => $referral['position'],
                    'joinedAt' => $referral['joined_at']
                ];
            },
            $this->donationRepository->findReferralsInFlower($user, $nextFlower)
        );
    }
}
