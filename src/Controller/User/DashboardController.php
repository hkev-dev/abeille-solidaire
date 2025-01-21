<?php

namespace App\Controller\User;

use App\Repository\DonationRepository;
use App\Repository\FlowerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository $flowerRepository,
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

        $data = [
            'user' => $user,
            'currentFlower' => $user->getCurrentFlower(),
            'walletBalance' => $user->getWalletBalance(),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'matrixChildren' => $user->getChildren(),
            'matrixChildrenCount' => $user->getChildren()->count(),
            'currentMembership' => $user->getCurrentMembership(),
            'flowerProgress' => $user->getFlowerProgress(),
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

