<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class MatrixVisualizationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getMatrixStructure(User $user, int $maxDepth = 4): array
    {
        $matrix = [
            'user' => $this->getUserInfo($user),
            'children' => []
        ];

        if ($maxDepth > 0) {
            foreach ($user->getChildren() as $child) {
                $matrix['children'][] = $this->getMatrixStructure($child, $maxDepth - 1);
            }
        }

        return $matrix;
    }

    public function getMatrixLevelStatus(User $user): array
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('u.matrixDepth, COUNT(u.id) as userCount')
            ->from(User::class, 'u')
            ->where('u.parent = :user')
            ->setParameter('user', $user)
            ->groupBy('u.matrixDepth')
            ->getQuery()
            ->getResult();
    }

    private function getUserInfo(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'matrixPosition' => $user->getMatrixPosition(),
            'matrixDepth' => $user->getMatrixDepth(),
            'currentFlower' => $user->getCurrentFlower()?->getName(),
            'hasCompletedCycle' => count($user->getChildren()) >= 4,
        ];
    }

    public function validateMatrixStructure(Donation $donation): bool
    {
        // Get all descendants grouped by depth
        $childrens = $donation->getChildrens();
        if (empty($childrens)) {
            return true; // No descendants yet is valid
        }

        $levelCounts = [];
        foreach ($descendants as $descendant) {
            $relativeDepth = $descendant->getMatrixDepth() - $user->getMatrixDepth();
            if (!isset($levelCounts[$relativeDepth])) {
                $levelCounts[$relativeDepth] = 0;
            }
            $levelCounts[$relativeDepth]++;
        }

        ksort($levelCounts); // Sort by depth
        
        $previousLevelComplete = true;
        $expectedUsers = 4;

        foreach ($levelCounts as $depth => $count) {
            // Each level should have exactly 4 users before moving to next level
            if (!$previousLevelComplete && $count > 0) {
                return false;
            }

            $previousLevelComplete = ($count === $expectedUsers);
            $expectedUsers *= 4; // Next level should have 4 times as many users
        }

        return true;
    }
}
