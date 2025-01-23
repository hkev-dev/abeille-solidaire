<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FlowerController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/user/flower/current', name: 'app.user.flower.current')]
    public function current(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $currentFlower = $user->getCurrentFlower();

        if (!$currentFlower) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // Initialize matrix positions with null values
        $matrixPositions = array_fill(1, 4, null);

        // Get direct children in the matrix
        $children = $user->getChildren();
        foreach ($children as $child) {
            $position = $child->getMatrixPosition();
            if ($position >= 1 && $position <= 4) {
                $matrixPositions[$position] = $child;
            }
        }

        $data = [
            'user' => $user,
            'flower' => $currentFlower,
            'allFlowers' => $this->getFlowerProgression($currentFlower),
            'walletBalance' => $user->getWalletBalance(),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'matrixPositions' => $matrixPositions,
            'membershipInfo' => [
                'isActive' => $user->hasPaidAnnualFee(),
                'expiresAt' => $user->getAnnualFeeExpiresAt(),
                'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
            ],
            'flowerProgress' => $user->getFlowerProgress(),
            'totalReceivedInFlower' => $this->donationRepository->getTotalReceivedInFlower($user, $currentFlower),
            'userLevel' => $user->getMatrixLevel(),    // Add this for proper level display
            'userDepth' => $user->getMatrixDepth(),    // Add this to fix the missing variable
            'completedCycles' => $this->donationRepository->countCompletedCycles($user, $currentFlower),
            'isKycVerified' => $user->isKycVerified(),
            'recentDonations' => $this->donationRepository->findRecentByUser($user, 5),
            'canWithdraw' => $user->isEligibleForWithdrawal() && $user->getWalletBalance() >= 50.0,
        ];

        return $this->render('user/pages/flower/current.html.twig', $data);
    }

    private function getFlowerProgression(Flower $currentFlower): array
    {
        $allFlowers = $this->flowerRepository->findBy([], ['level' => 'ASC']);
        $currentLevel = $currentFlower->getLevel();

        return array_map(
            function (Flower $flower) use ($currentLevel) {
                $flowerLevel = $flower->getLevel();
                return [
                    'id' => $flower->getId(),
                    'name' => $flower->getName(),
                    'donationAmount' => $flower->getDonationAmount(),
                    'isActive' => $flowerLevel === $currentLevel,
                    'isCompleted' => $flowerLevel < $currentLevel,
                    'isNext' => $flowerLevel === $currentLevel + 1,
                ];
            },
            $allFlowers
        );
    }
}
