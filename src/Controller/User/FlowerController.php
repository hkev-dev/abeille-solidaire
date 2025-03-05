<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use App\Service\FlowerService;
use App\Service\UserService;
use App\Service\WalletService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FlowerController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository   $flowerRepository,
        private readonly UserRepository     $userRepository,
        private readonly FlowerService      $flowerService,
        private readonly WalletService      $walletService,
        private readonly UserService $userService,
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

        $data = [
            'user' => $user,
            'flower' => $currentFlower,
            'allFlowers' => $this->flowerService->getFlowerProgression($currentFlower),
            'walletBalance' => $this->walletService->getWalletBalance($user),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'matrixPositions' => array_pad($user->getChildren()->toArray(), 4, null),
            'membershipInfo' => [
                'isActive' => $user->hasPaidAnnualFee(),
                'expiresAt' => $user->getMembershipExpiredAt(),
                'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
            ],
            'flowerProgress' => $user->getFlowerProgress(),
            'totalReceivedInFlower' => $this->flowerService->getReceivedAmount($user->getMainDonation(), $user->getCurrentFlower()),
            'userLevel' => $user->getMatrixLevel(),    // Add this for proper level display
            'userDepth' => $user->getMatrixDepth(),    // Add this to fix the missing variable
            'completedCycles' => $this->donationRepository->countCompletedCycles($user, $currentFlower),
            'isKycVerified' => $user->isKycVerified(),
            'recentDonations' => $this->donationRepository->findRecentByUser($user, 5),
            'canWithdraw' => $this->userService->isEligibleForWithdrawal($user),
        ];

        return $this->render('user/pages/flower/current.html.twig', $data);
    }
}
