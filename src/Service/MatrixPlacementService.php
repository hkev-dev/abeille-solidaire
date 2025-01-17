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
    private const MATRIX_POSITIONS = [
        [1, 2, 3, 4],
        [5, 6, 7, 8],
        [9, 10, 11, 12],
        [13, 14, 15, 16]
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationRepository $donationRepository,
        private readonly CacheInterface $cache,
        private readonly LockFactory $lockFactory
    ) {
    }

    public function findNextAvailablePosition(Flower $flower): ?User
    {
        try {
            $lock = $this->lockFactory->createLock("matrix_{$flower->getId()}", 30);
            if (!$lock->acquire()) {
                throw new LockConflictedException('Matrix is being processed');
            }

            try {
                if ($this->isMatrixFull($flower)) {
                    return null;
                }

                $matrixState = $this->getMatrixState($flower);
                $position = $this->calculateNextPosition($matrixState);

                if (!$position) {
                    return null;
                }

                // Find suitable user for this position
                return $this->findUserForPosition($flower);
            } finally {
                $lock->release();
            }
        } catch (\Exception $e) {
            // Log error if logger service is available
            return null;
        }
    }

    private function calculateNextPosition(array $matrixState): ?int
    {
        // Traverse matrix left-to-right, top-to-bottom
        foreach (self::MATRIX_POSITIONS as $row) {
            foreach ($row as $position) {
                if (!isset($matrixState[$position])) {
                    return $position;
                }
            }
        }

        return null;
    }

    public function isMatrixFull(Flower $flower): bool
    {
        try {
            $result = $this->cache->get(
                sprintf('matrix_state_%d', $flower->getId()),
                function () use ($flower) {
                    $positions = $this->donationRepository->findByFlowerMatrix($flower);
                    return count($positions) >= self::MATRIX_SIZE;
                }
            );
            
            return $result === true;
        } catch (\Exception $e) {
            // Log error if logger service is available
            return false;
        }
    }

    public function getMatrixState(Flower $flower): array
    {
        $cacheKey = sprintf('matrix_state_%d', $flower->getId());
        
        try {
            $result = $this->cache->get(
                $cacheKey,
                function () use ($flower) {
                    $positions = $this->donationRepository->findByFlowerMatrix($flower);
                    if (!is_array($positions)) {
                        return [];
                    }

                    $matrix = [];
                    foreach ($positions as $position) {
                        if (!isset($position['cyclePosition'], $position['recipient_id'])) {
                            continue;
                        }
                        
                        $matrix[$position['cyclePosition']] = [
                            'user_id' => $position['recipient_id'],
                            'joined_at' => $position['joined_at'] ?? new \DateTimeImmutable()
                        ];
                    }

                    return $matrix;
                }
            );

            if (!is_array($result)) {
                $this->cache->delete($cacheKey);
                return [];
            }

            return $result;
            
        } catch (\Exception $e) {
            // Log the error if you have a logger service
            return [];
        }
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

    public function visualizeMatrix(Flower $flower): array
    {
        $matrixState = $this->getMatrixState($flower);
        $visualization = [];

        foreach (self::MATRIX_POSITIONS as $rowIndex => $row) {
            $visualization[$rowIndex] = [];
            foreach ($row as $position) {
                $cell = [
                    'position' => $position,
                    'user' => null,
                    'joined_at' => null,
                    'is_occupied' => isset($matrixState[$position])
                ];

                if (isset($matrixState[$position])) {
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->find($matrixState[$position]['user_id']);

                    $cell['user'] = $user;
                    $cell['joined_at'] = $matrixState[$position]['joined_at'];
                }

                $visualization[$rowIndex][] = $cell;
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
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $result = $qb->select('u')
                ->from(User::class, 'u')
                ->where('u.currentFlower = :flower')
                ->andWhere('u.registrationPaymentStatus = :status')
                ->andWhere('u.isVerified = :verified')
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
                ->setParameter('status', 'completed')
                ->setParameter('verified', true)
                ->setParameter('types', ['direct', 'registration'])
                ->orderBy('u.waitingSince', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            return $result;
        } catch (\Exception $e) {
            // Log error if you have a logger service
            return null;
        }
    }

    public function findNextReferralPosition(User $referrer, Flower $flower): ?int
    {
        $lock = $this->lockFactory->createLock(
            sprintf('referral_matrix_%d_%d', $referrer->getId(), $flower->getId()),
            30
        );

        if (!$lock->acquire()) {
            throw new LockConflictedException('Referral matrix is being processed');
        }

        try {
            $matrixState = $this->getMatrixState($flower);
            if (empty($matrixState)) {
                return 1; // Return first position if matrix is empty
            }

            $referrerPositions = array_filter(
                $matrixState,
                fn($position) => ($position['user_id'] ?? null) === $referrer->getId()
            );

            // Find first available position after referrer's positions
            for ($position = 1; $position <= self::MATRIX_SIZE; $position++) {
                if (
                    !isset($matrixState[$position]) &&
                    $this->validateReferralPlacement($referrer, $position, $flower)
                ) {
                    return $position;
                }
            }

            return null;
        } finally {
            $lock->release();
        }
    }

    private function validateReferralPlacement(
        User $referrer,
        int $position,
        Flower $flower
    ): bool {
        // Ensure referrer has a position in this flower
        $referrerPositions = $this->donationRepository->findReferrerPositions($referrer, $flower);
        if (empty($referrerPositions)) {
            return false;
        }

        // Ensure position is valid
        if ($position < 1 || $position > self::MATRIX_SIZE) {
            return false;
        }

        // Add any additional validation rules here
        // For example, ensuring the position maintains proper matrix structure

        return true;
    }
}
