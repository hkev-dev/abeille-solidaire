<?php

namespace App\Service;

use App\Entity\Flower;
use App\Entity\User;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatrixPlacementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationRepository $donationRepository
    ) {}

    public function findNextAvailablePosition(Flower $flower): ?User
    {
        // Get all positions in the current flower's matrix
        $positions = $this->donationRepository->findByFlowerMatrix($flower);
        
        // Convert result to position map
        $positionMap = [];
        foreach ($positions as $position) {
            $positionMap[$position['cyclePosition']] = $position['recipient_id'];
        }
        
        // Matrix is 4x4, so maximum 16 positions
        for ($position = 1; $position <= 16; $position++) {
            if (!isset($positionMap[$position])) {
                // Found an empty position, find the user who should receive the donation
                return $this->findUserForPosition($flower);
            }
        }

        return null;
    }

    public function findNextPositionInReferrerMatrix(User $referrer, Flower $flower): ?int
    {
        $positions = $this->donationRepository->findByReferrerMatrix($referrer, $flower);
        
        // Find first available position from 1 to 16
        for ($position = 1; $position <= 16; $position++) {
            if (!isset($positions[$position])) {
                return $position;
            }
        }

        return null;
    }

    private function findUserForPosition(Flower $flower): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.currentFlower = :flower')
            ->andWhere(
                $qb->expr()->lt(
                    '(SELECT COUNT(d) FROM App\Entity\Donation d 
                      WHERE d.recipient = u 
                      AND d.flower = :flower 
                      AND d.donationType IN (:types))',
                    4
                )
            )
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration'])
            ->orderBy('u.waitingSince', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
