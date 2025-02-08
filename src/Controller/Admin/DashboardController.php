<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Donation;
use App\Entity\Withdrawal;
use App\Repository\DonationRepository;
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
    public function index(Session $session, DonationRepository $donationRepository, UserRepository $userRepository, WithdrawalRepository $withdrawalRepository): Response
    {
        $justLoggedIn = $session->get('justLoggedIn', false);
        $session->remove('justLoggedIn');

        $stats = [
            'users' => ['count' => $userRepository->count(), 'verifiedCount' => $userRepository->countVerified()],
            'donations' => ['count' => $donationRepository->countCompleted(), 'amount' => $donationRepository->getTotalAmount()],
            'withdraws' => ['amount' => $withdrawalRepository->getTotalAmount()]
        ];

        return $this->render('admin/pages/dashboard/index.html.twig', [
            'showChoiceModal' => $justLoggedIn,
            ...$stats
        ]);
    }
}
