<?php

namespace App\Service;

use App\Entity\Flower;
use App\Entity\User;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Exception\LockConflictedException;

class MatrixPlacementService
{
    private const MATRIX_SIZE = 16;
    private const MATRIX_ROWS = 4;
    private const MATRIX_COLS = 4;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationRepository $donationRepository,
        private readonly CacheInterface $cache,
        private readonly LockFactory $lockFactory
    ) {}

    public function findNextAvailablePosition(Flower $flower): ?User
    {
        if ($this->isMatrixFull($flower)) {
            return null;
        }

        $matrixState = $this->getMatrixState($flower);
        
        for ($position = 1; $position <= self::MATRIX_SIZE; $position++) {
            if (!isset($matrixState[$position])) {
                try {
                    $this->lockPosition($position, $flower);
                    if ($this->validatePlacement(null, $position)) {
                        return $this->findUserForPosition($flower);
                    }
                } catch (LockConflictedException) {
                    continue;
                }
            }
        }

        return null;
    }

    public function isMatrixFull(Flower $flower): bool
    {
        return $this->cache->get(
            sprintf('matrix_state_%d', $flower->getId()),
            function () use ($flower) {
                $positions = $this->donationRepository->findByFlowerMatrix($flower);
                return count($positions) >= self::MATRIX_SIZE;
            }
        );
    }

    public function getMatrixState(Flower $flower): array
    {
        return $this->cache->get(
            sprintf('matrix_state_%d', $flower->getId()),
            function () use ($flower) {
                $positions = $this->donationRepository->findByFlowerMatrix($flower);
                $matrix = [];
                foreach ($positions as $position) {
                    $matrix[$position['cyclePosition']] = $position['recipient_id'];
                }
                return $matrix;
            }
        );
    }

    public function lockPosition(int $position, Flower $flower): void
    {
        $lock = $this->lockFactory->createLock(
            sprintf('matrix_position_%d_%d', $flower->getId(), $position),
            30 // Lock TTL in seconds
        );

        if (!$lock->acquire()) {
            throw new LockConflictedException('Position is being processed');
        }
    }

    public function validatePlacement(?User $user, int $position): bool
    {
        if ($position < 1 || $position > self::MATRIX_SIZE) {
            return false;
        }

        // Additional validation logic can be added here
        // For example, checking user eligibility, position availability, etc.
        return true;
    }

    public function visualizeMatrix(Flower $flower): array
    {
        $matrixState = $this->getMatrixState($flower);
        $visualization = [];

        for ($row = 0; $row < self::MATRIX_ROWS; $row++) {
            $visualization[$row] = [];
            for ($col = 0; $col < self::MATRIX_COLS; $col++) {
                $position = ($row * self::MATRIX_COLS) + $col + 1;
                $visualization[$row][$col] = $matrixState[$position] ?? null;
            }
        }

        return $visualization;
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
