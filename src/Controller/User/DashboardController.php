<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\DonationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/user/dashboard', name: 'app.user.dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        // Calculate flower progress
        $currentFlower = $user->getCurrentFlower();
        $flowerProgress = [
            'received' => $this->donationRepository->countByRecipientAndFlower($user, $currentFlower),
            'total' => 4, // Always 4 donations needed per flower
            'percentage' => 0
        ];
        $flowerProgress['percentage'] = ($flowerProgress['received'] / $flowerProgress['total']) * 100;

        // Get membership info
        $membershipInfo = [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getAnnualFeeExpiresAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];

        $data = [
            'user' => $user,
            'currentFlower' => $currentFlower,
            'walletBalance' => $user->getWalletBalance(),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'matrixChildren' => $user->getChildren(),
            'matrixChildrenCount' => $user->getChildren()->count(),
            'membershipInfo' => $membershipInfo,
            'flowerProgress' => $flowerProgress,
            'recentDonations' => $this->donationRepository->findRecentByUser($user, 5),
            'matrixPosition' => [
                'depth' => $user->getMatrixDepth(),
                'position' => $user->getMatrixPosition(),
                'parent' => $user->getParent()
            ],
            'isKycVerified' => $user->isKycVerified(),
            'totalMembers' => $this->userRepository->countActiveMembers(),
        ];

        return $this->render('user/pages/dashboard/index.html.twig', $data);
    }
}

