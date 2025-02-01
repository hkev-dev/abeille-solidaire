<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Donation;
use App\Event\DonationProcessParentLevelUpEvent;
use App\Event\DonationPositionedInMatrixEvent;
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
        EntityManagerInterface     $em,
        DonationService            $donationService,
        FlowerService              $flowerService,
        MatrixVisualizationService $matrixVisualization,
        EventDispatcherInterface   $eventDispatcher,
        MembershipService          $membershipService
    )
    {
        $this->em = $em;
        $this->donationService = $donationService;
        $this->flowerService = $flowerService;
        $this->matrixVisualization = $matrixVisualization;
        $this->eventDispatcher = $eventDispatcher;
        $this->membershipService = $membershipService;
    }

    public function placeDonationInMatrix(Donation $donation): void
    {
        try {
            // Find available parent
            $parent = $this->findAvailableParent();
            $position = $this->getNextPosition();

            // Set matrix position and parent
            $donation->setParent($parent)
                ->setPaymentStatus('completed')
                ->setPaymentCompletedAt(new \DateTimeImmutable())
                ->setMatrixDepth($parent->getMatrixDepth() + 1)
                ->setMatrixPosition($position);

            $this->em->flush();

            // Dispatch donation level up event
            $event = new DonationPositionedInMatrixEvent($donation);
            $this->eventDispatcher->dispatch($event, $event::NAME);

            // Dispatch donation level up event
            $event = new DonationProcessParentLevelUpEvent($donation);
            $this->eventDispatcher->dispatch($event, $event::NAME);

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

    protected function findAvailableParent(): Donation
    {
        // Get root user (depth 0)
        $rootDonation = $this->em->getRepository(Donation::class)
            ->findOneBy(['paymentStatus' => 'completed'], ['paymentCompletedAt' => 'ASC']);

        if (!$rootDonation) {
            throw new \RuntimeException('Root Donation not found');
        }

        // Find first available parent using BFS with level validation
        $qb = $this->em->createQueryBuilder();
        $result = $qb->select('donation, COUNT(c.id) as childCount')
            ->from(Donation::class, 'donation')
            ->leftJoin('donation.childrens', 'c')
            ->where('donation.paymentStatus = :status')
            ->andWhere('donation.paymentCompletedAt IS NOT NULL')
            ->groupBy('donation.id')
            ->having($qb->expr()->lt('COUNT(c.id)', ':maxChildren'))
            ->orderBy('donation.paymentCompletedAt', 'ASC')
            ->setParameter('maxChildren', 4)
            ->setParameter('status', 'completed')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            // If no valid parent found, validate if root user can accept new children
            if (
                count($rootDonation->getChildrens()) < 4
            ) {
                return $rootDonation;
            }
            throw new \RuntimeException('Matrix is currently full');
        }

        return $result[0][0];
    }

    protected function getNextPosition(): int
    {
        $lastDonation = $this->em->createQueryBuilder()
            ->select('donation')
            ->from(Donation::class, 'donation')
            ->where('donation.paymentStatus = :status')
            ->andWhere('donation.paymentCompletedAt IS NOT NULL')
            ->andWhere('donation.matrixPosition IS NOT NULL')
            ->orderBy('donation.matrixPosition', 'DESC')
            ->setParameter('status', Donation::PAYMENT_COMPLETED)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        return $lastDonation->getMatrixPosition() + 1;
    }
}
