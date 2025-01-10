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
        $user = $this->getUser();
        
        $data = [
            'currentFlower' => $user->getCurrentFlower(),
            'walletBalance' => $user->getWalletBalance(),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'referralCount' => $user->getReferrals()->count(),
            'currentMembership' => $user->getCurrentMembership(),
            'flowerProgress' => $this->donationRepository->getCurrentFlowerProgress($user),
            'recentDonations' => $this->donationRepository->findRecentByUser($user, 5),
            'directReferrals' => $user->getReferrals()->slice(0, 4),
            'isKycVerified' => $user->isKycVerified(),
            'totalMembers' => $this->userRepository->countActiveMembers(),
        ];

        return $this->render('user/pages/dashboard/index.html.twig', $data);
    }
}
