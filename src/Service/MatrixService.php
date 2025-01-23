<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Donation;
use Doctrine\ORM\EntityManagerInterface;

class MatrixService
{
    protected EntityManagerInterface $em;
    protected DonationService $donationService;
    protected FlowerService $flowerService;

    public function __construct(
        EntityManagerInterface $em,
        DonationService $donationService,
        FlowerService $flowerService
    ) {
        $this->em = $em;
        $this->donationService = $donationService;
        $this->flowerService = $flowerService;
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
            $currentFlower = $user->getCurrentFlower();
            $flowerAmount = $currentFlower->getDonationAmount();

            $walletAmount = ($flowerAmount * 4) * 0.5;
            $solidarityAmount = ($flowerAmount * 4) * 0.5;

            // Add to user's wallet
            $user->addToWalletBalance($walletAmount);

            // First try to give solidarity amount to parent if exists
            $parent = $user->getParent();
            if ($parent) {
                $this->donationService->createDonation(
                    $user,
                    $parent,
                    $solidarityAmount,
                    Donation::TYPE_SOLIDARITY,
                    $user->getCurrentFlower()
                );
            } else {
                // User has no parent and hasn't been a parent, add to their wallet
                $user->addToWalletBalance($solidarityAmount);
            }

            $nextFlower = $this->flowerService->getNextFlower($currentFlower);
            $user->setCurrentFlower($nextFlower);

            // Sync children who completed their cycles""
            $this->syncChildrenFlowers($user);

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

        // Find first available parent using BFS
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, COUNT(c.id) as childCount')
            ->from(User::class, 'u')
            ->leftJoin('u.children', 'c')
            ->where('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->groupBy('u.id')
            ->having($qb->expr()->lt('COUNT(c.id)', ':maxChildren'))
            ->orderBy('u.matrixDepth', 'ASC')
            ->addOrderBy('u.id', 'ASC')  // Ensure sequential filling within same depth
            ->setParameter('maxChildren', 4);

        $result = $qb->getQuery()->getResult();

        if (empty($result)) {
            return $rootUser;
        }

        // Return the first user with less than 4 children
        foreach ($result as $row) {
            return $row[0];
        }

        throw new \RuntimeException('No available parent found in the matrix');
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

    protected function syncChildrenFlowers(User $user): void
    {
        foreach ($user->getChildren() as $child) {
            if ($this->donationService->hasCompletedCycle($child)) {
                $child->setCurrentFlower($user->getCurrentFlower());
            }
        }
    }
}