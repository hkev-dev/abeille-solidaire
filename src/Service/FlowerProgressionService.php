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
        private readonly SolidarityDonationService $solidarityDonationService, // Replace DonationService
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

            // Process solidarity donation using the new service
            $recipient = $this->matrixPlacementService->findSolidarityRecipient($flower);
            if ($recipient) {
                $this->solidarityDonationService->processSolidarityDonation(
                    $user,
                    $solidarityAmount,
                    $flower
                );
            }

            // Record cycle completion
            $this->recordCycleCompletion($user, $flower, $cycleInfo['currentCycleNumber']);

            // Progress to next flower if all cycles are completed
            if ($cycleInfo['totalCompletedCycles'] + 1 >= 10) {
                $nextFlower = $this->progressToNextFlower($user, $flower);
                
                // Find new matrix position in next flower
                if ($nextFlower) {
                    $newPosition = $this->matrixPlacementService->findNextAvailablePosition($nextFlower);
                    if ($newPosition) {
                        $matrixDetails = $this->matrixPlacementService->calculateMatrixPosition($newPosition);
                        $user->setMatrixDepth($matrixDetails['depth'])
                            ->setMatrixPosition($matrixDetails['position'])
                            ->setCurrentFlower($nextFlower);
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Dispatch completion event with matrix details
            $event = new FlowerCycleCompletedEvent(
                $user,
                $flower,
                $user->getCurrentFlower(),
                $walletAmount
            );
            $this->eventDispatcher->dispatch($event, FlowerCycleCompletedEvent::NAME);

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

    private function progressToNextFlower(User $user, Flower $flower): ?Flower
    {
        $nextFlower = $this->flowerRepository->findNextFlower($flower);
        if (!$nextFlower) {
            return null;
        }

        // Find available matrix position in new flower
        $position = $this->matrixPlacementService->findNextAvailablePosition($nextFlower);
        if (!$position) {
            throw new \RuntimeException('No available positions in next flower matrix');
        }

        // Calculate matrix details for new position
        $matrixDetails = $this->matrixPlacementService->calculateMatrixPosition($position);
        $parent = $this->matrixPlacementService->findParentUser($matrixDetails['depth'], $matrixDetails['position']);

        // Update user's matrix information
        $user->setCurrentFlower($nextFlower)
            ->setMatrixDepth($matrixDetails['depth'])
            ->setMatrixPosition($matrixDetails['position'])
            ->setParent($parent);

        // Create matrix progression donation if there's a parent
        if ($parent) {
            $donation = new Donation();
            $donation->setDonationType('matrix_progression')
                ->setDonor($user)
                ->setRecipient($parent)
                ->setFlower($nextFlower)
                ->setCyclePosition($position)
                ->setAmount($nextFlower->getDonationAmount())
                ->setTransactionDate(new \DateTimeImmutable());

            $this->entityManager->persist($donation);
        }

        // Lock the position in new flower's matrix
        $this->matrixPlacementService->lockPosition($position, $nextFlower);

        return $nextFlower;
    }

    public function getCurrentMatrixInfo(User $user): array
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return [];
        }

        return [
            'flower' => $currentFlower,
            'depth' => $user->getMatrixDepth(),
            'position' => $user->getMatrixPosition(),
            'parent' => $user->getParent(),
            'children' => $this->matrixPlacementService->getChildrenInMatrix($user),
            'donationsReceived' => $this->donationRepository->countReceivedDonationsInPosition($user, $currentFlower)
        ];
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
                    'matrixDepth' => $cycle['matrix_depth'],
                    'matrixPosition' => $cycle['matrix_position']
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

        $matrixInfo = $this->getCurrentMatrixInfo($user);
        $requirements = [];

        // Requirement 1: Matrix Position Completion
        $requirements[] = [
            'label' => 'Complete Current Matrix Position',
            'description' => sprintf('Receive %d more donations in position %d',
                4 - $matrixInfo['donationsReceived'],
                $matrixInfo['position']
            ),
            'fulfilled' => $matrixInfo['donationsReceived'] >= 4
        ];

        // Requirement 2: Matrix Depth
        $requirements[] = [
            'label' => 'Matrix Depth Requirement',
            'description' => sprintf('Current matrix depth: %d (minimum 4 required)',
                $matrixInfo['depth']
            ),
            'fulfilled' => $matrixInfo['depth'] >= 4
        ];

        // Requirement 3: KYC Verification
        $requirements[] = [
            'label' => 'KYC Verification',
            'description' => $user->isKycVerified() ?
                'KYC verified on ' . $user->getKycVerifiedAt()?->format('d/m/Y') :
                'KYC verification required',
            'fulfilled' => $user->isKycVerified()
        ];

        // Requirement 4: Annual Membership
        $requirements[] = [
            'label' => 'Annual Membership',
            'description' => $user->hasPaidAnnualFee() ?
                'Annual membership active' :
                'Annual membership required',
            'fulfilled' => $user->hasPaidAnnualFee()
        ];

        return $requirements;
    }

    public function getMatrixVisualization(User $user): array
    {
        return $this->matrixPlacementService->visualizeMatrix($user->getCurrentFlower());
    }

    /**
     * Calculates next matrix position for user progression
     */
    private function calculateNextMatrixPosition(User $user, Flower $nextFlower): ?array
    {
        $position = $this->matrixPlacementService->findNextAvailablePosition($nextFlower);
        if (!$position) {
            return null;
        }

        return $this->matrixPlacementService->calculateMatrixPosition($position);
    }

    /**
     * Validates matrix requirements for progression
     */
    private function validateMatrixRequirements(User $user): bool
    {
        $matrixInfo = $this->getCurrentMatrixInfo($user);
        
        return $user->hasPaidAnnualFee() && 
               $matrixInfo['depth'] >= 3 && 
               $matrixInfo['donationsReceived'] >= 4;
    }

    /**
     * Gets matrix statistics for a user
     */
    public function getMatrixStats(User $user): array
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return [];
        }

        return [
            'matrix_depth' => $user->getMatrixDepth(),
            'matrix_position' => $user->getMatrixPosition(),
            'total_children' => count($this->matrixPlacementService->getChildrenInMatrix($user)),
            'donations_received' => $this->donationRepository->countReceivedDonationsInPosition($user, $currentFlower),
            'total_earned' => $this->getTotalEarned($user),
            'can_progress' => $this->validateMatrixRequirements($user)
        ];
    }
}
