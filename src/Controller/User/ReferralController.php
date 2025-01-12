<?php

namespace App\Controller\User;

use App\Repository\DonationRepository;
use App\Repository\FlowerRepository;
use App\Repository\UserRepository;
use App\Service\ReferralService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/referral', name: 'app.user.referral.')]
class ReferralController extends AbstractController
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly UserRepository $userRepository,
        private readonly DonationRepository $donationRepository,
    ) {
    }

    #[Route('/referrals', name: 'referrals')]
    public function referrals(): Response
    {
        $user = $this->getUser();

        return $this->render('user/pages/referral/referrals.html.twig', [
            'directReferrals' => $this->referralService->getDirectReferrals($user),
            'referralCount' => $user->getReferrals()->count(),
            'canAcceptNewReferrals' => $this->referralService->canAcceptNewReferrals($user),
            'referralStats' => [
                'totalEarnings' => $this->donationRepository->findTotalReferralEarningsForUser($user),
                'activeReferrals' => count(array_filter($user->getReferrals()->toArray(), fn($ref) => $ref->isVerified())),
                'pendingReferrals' => count(array_filter($user->getReferrals()->toArray(), fn($ref) => !$ref->isVerified()))
            ]
        ]);
    }

    #[Route('/link', name: 'link')]
    public function link(): Response
    {
        $user = $this->getUser();
        $referralUrl = $this->generateUrl('app.register', [
            'ref' => $user->getReferralCode()
        ], true);

        return $this->render('user/pages/referral/link.html.twig', [
            'referralUrl' => $referralUrl,
            'referralCode' => $user->getReferralCode()
        ]);
    }

    #[Route('/stats', name: 'stats')]
    public function stats(): Response
    {
        $user = $this->getUser();
        
        // Get earnings data for chart
        $monthlyEarnings = $this->donationRepository->findMonthlyReferralEarnings($user);
        
        return $this->render('user/pages/referral/stats.html.twig', [
            'referralPath' => $this->referralService->getReferralPath($user),
            'totalEarnings' => $this->donationRepository->findTotalReferralEarningsForUser($user),
            'flowerStats' => $this->userRepository->findReferralStatsByFlower($user),
            'earningsData' => array_values($monthlyEarnings),
            'earningsDates' => array_keys($monthlyEarnings)
        ]);
    }
}
