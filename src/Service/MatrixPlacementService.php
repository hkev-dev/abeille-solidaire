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
        $positions = $this->donationRepository->getFlowerMatrix($flower);
        
        // Matrix is 4x4, so maximum 16 positions
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $position = $row * 4 + $col;
                if (!isset($positions[$position])) {
                    // Found an empty position, find the user who should receive the donation
                    return $this->getUserForPosition($flower, $row, $col);
                }
            }
        }

        return null;
    }

    public function findNextPositionInReferrerMatrix(User $referrer, Flower $flower): ?int
    {
        $positions = $this->donationRepository->getReferrerMatrixPositions($referrer, $flower);
        
        // Matrix is 4x4, so maximum 16 positions
        for ($position = 0; $position < 16; $position++) {
            if (!isset($positions[$position])) {
                return $position;
            }
        }

        return null;
    }

    private function getUserForPosition(Flower $flower, int $row, int $col): ?User
    {
        // Get users in the current flower who haven't received maximum donations
        $query = $this->entityManager->createQuery(
            'SELECT u FROM App\Entity\User u
            WHERE u.currentFlower = :flower
            AND (
                SELECT COUNT(d) FROM App\Entity\Donation d
                WHERE d.recipient = u AND d.flower = :flower
            ) < 4
            ORDER BY u.waitingSince ASC'
        )->setParameter('flower', $flower);

        return $query->setMaxResults(1)->getOneOrNullResult();
    }
}
