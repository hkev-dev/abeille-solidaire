<?php

namespace App\Service;

use App\Entity\Flower;
use App\Entity\User;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Psr\Log\LoggerInterface;

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
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function findNextAvailablePosition(Flower $flower): array
    {
        $lock = $this->lockFactory->createLock("matrix_{$flower->getId()}", 30);

        if (!$lock->acquire()) {
            throw new LockConflictedException('Matrix is being processed');
        }

        try {
            // First, check if we have a root user
            $rootUser = $this->findRootUser();
            
            // If no root user exists, create the root position
            if (!$rootUser) {
                return [
                    'position' => 1,
                    'depth' => 0,
                    'parent' => null
                ];
            }

            // Get count of direct children of root user
            $rootChildrenCount = $this->entityManager->createQueryBuilder()
                ->select('COUNT(u.id)')
                ->from(User::class, 'u')
                ->where('u.parent = :root')
                ->andWhere('u.currentFlower = :flower')
                ->setParameter('root', $rootUser)
                ->setParameter('flower', $flower)
                ->getQuery()
                ->getSingleScalarResult();

            // If root doesn't have all 4 children yet, add to first level
            if ($rootChildrenCount < 4) {
                return [
                    'position' => $rootChildrenCount + 2, // +2 because positions start at 2 for first level
                    'depth' => 1,
                    'parent' => $rootUser
                ];
            }

            // If we're here, root has all children, proceed with normal placement
            $maxDepth = $this->entityManager->createQueryBuilder()
                ->select('MAX(u.matrixDepth)')
                ->from(User::class, 'u')
                ->where('u.currentFlower = :flower')
                ->setParameter('flower', $flower)
                ->getQuery()
                ->getSingleScalarResult() ?? 1; // Start at depth 1 if no results

            // Get count of users at current max depth
            $usersAtCurrentDepth = $this->entityManager->createQueryBuilder()
                ->select('COUNT(u.id)')
                ->from(User::class, 'u')
                ->where('u.currentFlower = :flower')
                ->andWhere('u.matrixDepth = :depth')
                ->setParameter('flower', $flower)
                ->setParameter('depth', $maxDepth)
                ->getQuery()
                ->getSingleScalarResult();

            // Calculate positions for current depth
            $positionsAtDepth = pow(4, $maxDepth);

            if ($usersAtCurrentDepth < $positionsAtDepth) {
                // Find first parent at previous level with available slots
                $potentialParents = $this->entityManager->createQueryBuilder()
                    ->select('u')
                    ->from(User::class, 'u')
                    ->where('u.currentFlower = :flower')
                    ->andWhere('u.matrixDepth = :parentDepth')
                    ->andWhere('u.isVerified = true')
                    ->andWhere('u.registrationPaymentStatus = :status')
                    ->setParameter('flower', $flower)
                    ->setParameter('parentDepth', $maxDepth - 1)
                    ->setParameter('status', 'completed')
                    ->getQuery()
                    ->getResult();

                foreach ($potentialParents as $potentialParent) {
                    if ($potentialParent->getChildren()->count() < 4) {
                        $nextPosition = pow(4, $maxDepth) + ($potentialParent->getChildren()->count());
                        return [
                            'position' => $nextPosition,
                            'depth' => $maxDepth,
                            'parent' => $potentialParent
                        ];
                    }
                }
            }

            // If we get here, we need to start a new level
            $maxDepth++;
            $firstParentInPreviousLevel = $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->where('u.currentFlower = :flower')
                ->andWhere('u.matrixDepth = :depth')
                ->andWhere('u.isVerified = true')
                ->andWhere('u.registrationPaymentStatus = :status')
                ->setParameter('flower', $flower)
                ->setParameter('depth', $maxDepth - 1)
                ->setParameter('status', 'completed')
                ->orderBy('u.matrixPosition', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$firstParentInPreviousLevel) {
                throw new \RuntimeException('Could not find parent in previous level');
            }

            return [
                'position' => pow(4, $maxDepth),
                'depth' => $maxDepth,
                'parent' => $firstParentInPreviousLevel
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error finding next available position', [
                'flower_id' => $flower->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function findFirstAvailablePositionAtDepth(Flower $flower, int $depth): int
    {
        $startPosition = pow(4, $depth);
        $endPosition = pow(4, $depth + 1);

        // Find all occupied positions at this depth
        $occupiedPositions = $this->entityManager->createQueryBuilder()
            ->select('u.matrixPosition')
            ->from(User::class, 'u')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.matrixDepth = :depth')
            ->setParameter('flower', $flower)
            ->setParameter('depth', $depth)
            ->getQuery()
            ->getArrayResult();

        $occupiedPositions = array_column($occupiedPositions, 'matrixPosition');

        // Find first available position
        for ($pos = $startPosition; $pos < $endPosition; $pos++) {
            if (!in_array($pos, $occupiedPositions)) {
                return $pos;
            }
        }

        throw new \RuntimeException('No available positions at depth ' . $depth);
    }

    private function findFirstAvailableParent(Flower $flower): ?User
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u, COUNT(c.id) as childCount')
            ->from(User::class, 'u')
            ->leftJoin('u.children', 'c')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
            ->groupBy('u.id')  // Include all selected non-aggregated fields
            ->having('COUNT(c.id) < 4')
            ->setParameter('flower', $flower)
            ->setParameter('status', 'completed')
            ->orderBy('u.matrixDepth', 'ASC')
            ->addOrderBy('u.matrixPosition', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findNextChildPosition(User $parent): int
    {
        $childCount = $parent->getChildren()->count();
        if ($childCount >= 4) {
            throw new \RuntimeException('Parent has no available child positions');
        }
        return $childCount + 1;
    }

    private function findUserByMatrixPosition(Flower $flower, int $position): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findUserByMatrixPosition($flower, $position);
    }

    private function getCurrentMatrixLevel(array $matrixState): int
    {
        if (empty($matrixState)) {
            return 0;
        }

        // Find the highest occupied position
        $maxPosition = max(array_keys($matrixState));
        return (int) ceil(log(($maxPosition + 3) / 4, 4));
    }

    private function isLevelComplete(array $matrixState, int $level): bool
    {
        $startPosition = pow(4, $level - 1);
        $endPosition = pow(4, $level);

        for ($pos = $startPosition; $pos < $endPosition; $pos++) {
            if (!isset($matrixState[$pos])) {
                return false;
            }
        }

        return true;
    }

    private function findNextPositionInLevel(array $matrixState, int $level): ?int
    {
        $startPosition = pow(4, $level);
        $endPosition = pow(4, $level + 1);

        for ($pos = $startPosition; $pos < $endPosition; $pos++) {
            if (!isset($matrixState[$pos])) {
                return $pos;
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
        $maxLevel = empty($matrixState) ? 0 : $this->getCurrentMatrixLevel($matrixState);
        $visualization = [];

        // Initialize matrix structure
        for ($level = 0; $level <= $maxLevel; $level++) {
            $startPosition = $level === 0 ? 1 : pow(4, $level);
            $endPosition = pow(4, $level + 1);
            $levelNodes = [];

            for ($position = $startPosition; $position < $endPosition; $position++) {
                $cell = [
                    'position' => $position,
                    'user' => null,
                    'joined_at' => null,
                    'is_occupied' => isset($matrixState[$position]),
                    'children' => $this->getChildrenPositions($position),
                    'parent' => $this->getParentPosition($position),
                    'level' => $level
                ];

                if (isset($matrixState[$position])) {
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->find($matrixState[$position]['user_id']);

                    if ($user) {
                        $cell['user'] = [
                            'id' => $user->getId(),
                            'email' => $user->getEmail(),
                            'name' => $user->getFullName(),
                            'matrix_depth' => $user->getMatrixDepth(),
                            'matrix_position' => $user->getMatrixPosition()
                        ];
                        $cell['joined_at'] = $matrixState[$position]['joined_at'];
                    }
                }

                $levelNodes[] = $cell;
            }

            $visualization[] = [
                'level' => $level,
                'nodes' => $levelNodes,
                'is_complete' => $this->isLevelComplete($matrixState, $level),
                'total_positions' => count($levelNodes),
                'filled_positions' => count(array_filter($levelNodes, fn($node) => $node['is_occupied']))
            ];
        }

        return [
            'flower' => [
                'id' => $flower->getId(),
                'name' => $flower->getName(),
                'donation_amount' => $flower->getDonationAmount()
            ],
            'matrix' => $visualization,
            'stats' => [
                'total_levels' => $maxLevel + 1,
                'total_positions' => array_sum(array_map(fn($level) => $level['total_positions'], $visualization)),
                'filled_positions' => array_sum(array_map(fn($level) => $level['filled_positions'], $visualization)),
                'is_matrix_full' => $this->isMatrixFull($flower)
            ]
        ];
    }

    private function getChildrenPositions(int $position): array
    {
        $childStartPosition = ($position * 4) - 2;
        $children = [];

        for ($i = 0; $i < 4; $i++) {
            $childPosition = $childStartPosition + $i;
            if ($childPosition > 0 && $childPosition <= self::MATRIX_SIZE) {
                $children[] = $childPosition;
            }
        }

        return $children;
    }

    private function getParentPosition(int $position): ?int
    {
        if ($position <= 1) {
            return null;
        }

        // For any position n > 1, the parent position is floor((n-1)/4)
        return (int) floor(($position - 1) / 4);
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
                ->andWhere('u.waitingSince IS NOT NULL')
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

            if ($result) {
                $this->logger->info('Found user for matrix position', [
                    'user_id' => $result->getId(),
                    'flower' => $flower->getName(),
                    'waiting_since' => $result->getWaitingSince()?->format('Y-m-d H:i:s')
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error finding user for matrix position', [
                'flower' => $flower->getName(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validates a matrix position is within bounds and follows matrix rules
     */
    private function validateMatrixPosition(int $position, array $matrixState): bool
    {
        if ($position < 1 || $position > self::MATRIX_SIZE) {
            $this->logger->warning('Invalid matrix position', [
                'position' => $position,
                'max_size' => self::MATRIX_SIZE
            ]);
            return false;
        }

        // Get parent position
        $parentPosition = $this->getParentPosition($position);

        // Root position (1) is always valid if empty
        if ($parentPosition === null) {
            return !isset($matrixState[$position]);
        }

        // Position is valid if parent exists and is occupied
        $isValid = isset($matrixState[$parentPosition]) && !isset($matrixState[$position]);

        if (!$isValid) {
            $this->logger->warning('Invalid matrix position placement', [
                'position' => $position,
                'parent_position' => $parentPosition,
                'parent_exists' => isset($matrixState[$parentPosition]),
                'position_occupied' => isset($matrixState[$position])
            ]);
        }

        return $isValid;
    }

    /**
     * Calculates the matrix position details from a given position number
     */
    public function calculateMatrixPosition(int $position): array
    {
        if ($position < 1 || $position > self::MATRIX_SIZE) {
            throw new \InvalidArgumentException('Invalid matrix position');
        }

        $depth = (int) floor(log($position, 4));
        $positionInLevel = $position - pow(4, $depth);

        return [
            'depth' => $depth,
            'position' => $positionInLevel,
            'parent_position' => $this->getParentPosition($position),
            'children_positions' => $this->getChildrenPositions($position)
        ];
    }

    /**
     * Finds children in the matrix for a given user
     */
    public function getChildrenInMatrix(User $user): array
    {
        return $this->entityManager->getRepository(User::class)
            ->findBy([
                'parent' => $user,
                'currentFlower' => $user->getCurrentFlower()
            ]);
    }

    /**
     * Finds a suitable recipient for solidarity donations
     */
    public function findSolidarityRecipient(Flower $flower): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.currentFlower = :flower')
            ->andWhere('u.isVerified = true')
            ->andWhere('u.registrationPaymentStatus = :status')
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
            ->setParameter('types', ['direct', 'solidarity'])
            ->orderBy('u.waitingSince', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds position in matrix for a specific user
     */
    public function findNextPositionForUser(User $user, Flower $flower): ?int
    {
        $currentPosition = $user->getMatrixPosition();
        if (!$currentPosition) {
            return $this->findNextAvailablePosition($flower);
        }

        $matrixState = $this->getMatrixState($flower);
        $childPositions = $this->getChildrenPositions($currentPosition);

        foreach ($childPositions as $position) {
            if (!isset($matrixState[$position])) {
                return $position;
            }
        }

        return null;
    }

    /**
     * Finds parent user for a given matrix position
     */
    public function findParentUser(int $depth, int $position): ?User
    {
        if ($depth <= 0) {
            return null;
        }

        $parentPosition = $this->getParentPosition($position);
        if (!$parentPosition) {
            return null;
        }

        return $this->entityManager->getRepository(User::class)
            ->findOneBy([
                'matrixPosition' => $parentPosition
            ]);
    }

    private function findRootUser(): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy([
                'matrixPosition' => 1,
                'matrixDepth' => 0,
                'parent' => null
            ]);
    }
}
