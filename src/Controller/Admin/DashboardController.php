<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Donation;
use App\Entity\Withdrawal;
use App\Repository\DonationRepository;
use App\Repository\MembershipRepository;
use App\Repository\PonctualDonationRepository;
use App\Repository\UserRepository;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app.admin.dashboard')]
    public function index(
        Session $session,
        DonationRepository $donationRepository,
        UserRepository $userRepository,
        PonctualDonationRepository $ponctualDonationRepository,
        MembershipRepository $membershipRepository,
        WithdrawalRepository $withdrawalRepository
    ): Response {
        $justLoggedIn = $session->get('justLoggedIn', false);
        $session->remove('justLoggedIn');

        $stats = [
            'recentUsers' => $userRepository->findRecent(),
            'recentDonations' => $ponctualDonationRepository->findRecent(),
            'users' => ['count' => $userRepository->count(), 'verifiedCount' => $userRepository->countVerified()],
            'donations' => ['count' => $donationRepository->countCompleted(), 'amount' => $donationRepository->getTotalAmount()],
            'memberships' => ['count' => $membershipRepository->countCompleted(), 'amount' => $membershipRepository->getTotalAmount()],
            'withdraws' => ['amount' => $withdrawalRepository->getTotalAmount()],
            'graph' => [
                'donations' => json_encode($this->formatToGraph($donationRepository->getGraphData())),
                'withdrawals' => json_encode($this->formatToGraph($withdrawalRepository->getGraphData()))
            ]
        ];


        return $this->render('admin/pages/dashboard/index.html.twig', [
            'showChoiceModal' => $justLoggedIn,
            ...$stats
        ]);
    }

    private function formatToGraph(mixed $stats): array
    {
        $keys = [];
        $data = [];

        foreach ($stats as $item) {
            $key = $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT);
            $keys[] = $key;
            $data[] = (float) $item['totalAmount'];
        }

        return [
            'keys' => $keys,
            'data' => $data
        ];
    }
}
