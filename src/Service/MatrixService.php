<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Donation;
use App\Event\ParentFlowerUpdateEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MatrixService
{
    protected EntityManagerInterface $em;
    protected DonationService $donationService;
    protected FlowerService $flowerService;
    protected MatrixVisualizationService $matrixVisualization;
    protected EventDispatcherInterface $eventDispatcher;
    protected MembershipService $membershipService;

    public function __construct(
        EntityManagerInterface $em,
        DonationService $donationService,
        FlowerService $flowerService,
        MatrixVisualizationService $matrixVisualization,
        EventDispatcherInterface $eventDispatcher,
        MembershipService $membershipService
    ) {
        $this->em = $em;
        $this->donationService = $donationService;
        $this->flowerService = $flowerService;
        $this->matrixVisualization = $matrixVisualization;
        $this->eventDispatcher = $eventDispatcher;
        $this->membershipService = $membershipService;
    }

    public function placeUserInMatrix(User $user): void
    {
        try {
            // Find available parent
            $parent = $this->findAvailableParent();
            $position = $this->calculatePosition($parent);

            // Set matrix position and parent
            $user->setParent($parent)
                ->setMatrixDepth($parent->getMatrixDepth() + 1)
                ->setMatrixPosition($position);

            // Set initial flower to same as parent's current flower
            $user->setCurrentFlower($parent->getCurrentFlower());

            // Create registration donation to parent with explicit pending status
            $this->donationService->createDonation(
                $user,
                $parent,
                25.00,
                Donation::TYPE_REGISTRATION,
                $parent->getCurrentFlower(),
                'pending' // Explicitly set as pending until payment is confirmed
            );

            $this->em->flush();

            // Process parent's cycle completion if needed
            if ($this->donationService->hasCompletedCycle($parent)) {
                $this->processUserCycleCompletion($parent);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function processUserCycleCompletion(User $user): void
    {
        try {
            // Validate matrix structure
            if (!$this->matrixVisualization->validateMatrixStructure($user)) {
                throw new \RuntimeException('Matrix structure is invalid');
            }

            // Validate flower progression
            if (!$this->flowerService->validateFlowerProgression($user)) {
                throw new \RuntimeException('User cannot progress to next flower');
            }

            $currentFlower = $user->getCurrentFlower();
            $flowerAmount = $currentFlower->getDonationAmount();

            // Calculate amounts
            $walletAmount = ($flowerAmount * 4) * 0.5;
            $solidarityAmount = ($flowerAmount * 4) * 0.5;

            // Add to user's wallet
            $user->addToWalletBalance($walletAmount);

            // Create solidarity donation to Abeille Solidaire
            $this->donationService->createSolidarityDonation(
                $user,
                $solidarityAmount,
                $currentFlower
            );

            // Dispatch parent flower update event
            $event = new ParentFlowerUpdateEvent($user);
            $this->eventDispatcher->dispatch($event, ParentFlowerUpdateEvent::NAME);

            $this->em->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function findAvailableParent(): User
    {
        // Get root user (depth 0)
        $rootUser = $this->em->getRepository(User::class)
            ->findOneBy(['matrixDepth' => 0]);

        if (!$rootUser) {
            throw new \RuntimeException('Root user not found');
        }

        // Find first available parent using BFS with level validation
        $qb = $this->em->createQueryBuilder();
        $result = $qb->select('u, COUNT(c.id) as childCount')
            ->from(User::class, 'u')
            ->leftJoin('u.children', 'c')
            ->where('u.registrationPaymentStatus = :status')
            ->andWhere('u.isKycVerified = :kyc')
            ->setParameter('status', 'completed')
            ->setParameter('kyc', true)
            ->groupBy('u.id')
            ->having($qb->expr()->lt('COUNT(c.id)', ':maxChildren'))
            ->orderBy('u.matrixDepth', 'ASC')
            ->addOrderBy('u.id', 'ASC')
            ->setParameter('maxChildren', 4)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            // If no valid parent found, validate if root user can accept new children
            if (count($rootUser->getChildren()) < 4 && 
                $this->membershipService->canParticipateInMatrix($rootUser)) {
                return $rootUser;
            }
            throw new \RuntimeException('Matrix is currently full');
        }

        // Validate matrix level filling and membership
        foreach ($result as $row) {
            $parent = $row[0];
            if ($this->matrixVisualization->validateMatrixStructure($parent) &&
                $this->membershipService->canParticipateInMatrix($parent)) {
                return $parent;
            }
        }

        throw new \RuntimeException('No available parent found with valid matrix structure');
    }

    protected function calculatePosition(User $parent): int
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult() + 1;
    }

    public function getMatrixVisualization(User $user): array
    {
        return $this->matrixVisualization->getMatrixStructure($user);
    }

    public function validateUserMatrixDepth(User $user): bool
    {
        if (!$this->membershipService->canParticipateInMatrix($user)) {
            return false;
        }

        return $user->getMatrixDepth() >= 3 && 
               $this->matrixVisualization->validateMatrixStructure($user);
    }

    public function validateUserForWithdrawal(User $user): bool
    {
        return $user->isKycVerified() &&
               $this->membershipService->canParticipateInMatrix($user) &&
               $this->validateUserMatrixDepth($user) &&
               $user->hasProject();
    }
}
