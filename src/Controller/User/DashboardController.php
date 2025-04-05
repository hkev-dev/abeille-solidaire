<?php

namespace App\Controller\User;

use App\Entity\Donation;
use App\Entity\Earning;
use App\Entity\User;
use App\Repository\DonationRepository;
use App\Repository\EarningRepository;
use App\Repository\UserRepository;
use App\Repository\WithdrawalRepository;
use App\Service\UserService;
use App\Service\WalletService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository   $donationRepository,
        private readonly UserRepository       $userRepository,
        private readonly WithdrawalRepository $withdrawalRepository,
        private readonly EarningRepository $earningRepository,
        private readonly WalletService $walletService,
        private readonly UserService $userService,
    ) {
    }

    #[Route('/user/dashboard', name: 'app.user.dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(Session $session): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get current flower first
        $currentFlower = $user->getCurrentFlower();

        // Early return if no flower is assigned yet
        if (!$currentFlower) {
            throw $this->createAccessDeniedException('No flower assigned yet.');
        }

        $flowerProgress = $user->getFlowerProgress();

        // Get membership info
        $membershipInfo = [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getMembershipExpiredAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];

        #var_dump($membershipInfo, $user->getLastMembership()->getEndDate()->diff(new \DateTime())->days);die;

        // Calculate withdrawal eligibility
        $canWithdraw = $this->userService->isEligibleForWithdrawal($user);

        // Format recent activities
        $recentActivity = $this->formatRecentActivity($user);

        $justLoggedIn = $session->get('justLoggedIn', false);
        $session->remove('justLoggedIn');

        $data = [
            'user' => $user,
            'showChoiceModal' => $justLoggedIn,
            'currentFlower' => $currentFlower,
            'walletBalance' => $this->walletService->getWalletBalance($user),
            'totalDonationsReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'totalDonationsMade' => $this->donationRepository->getTotalMadeByUser($user),
            'matrixChildren' => $user->getChildrenDonation(),
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
            'recentActivity' => $recentActivity,
            'canWithdraw' => $canWithdraw,
        ];

        return $this->render('user/pages/dashboard/index.html.twig', $data);
    }

    private function formatRecentActivity(User $user): array
    {
        $activities = [];

        // Get recent donations (received and made)
        $recentEarnings = $this->earningRepository->findEarned($user);
        /** @var Earning $earning */
        foreach ($recentEarnings as $earning) {
            $donor = $earning->getDonor();
            $activities[] = [
                'type' => $this->formatDonationType($donor->getDonationType()),
                'icon' => $this->getDonationIcon($donor->getDonationType(), false),
                'color' => $this->getDonationColor($donor->getDonationType(), false),
                'description' => $this->formatEarningDescription($earning),
                'amount' => $earning->getAmount(),
                'date' => $earning->getCreatedAt(),
                'status' => $this->formatPaymentStatus("completed"),
                'statusColor' => $this->getPaymentStatusColor("completed")
            ];
        }
        /** @var Donation $donation */
        foreach ($user->getDonationsMade() as $donation) {
            if ($donation->getAmount() <= 0) {
                continue;
            }

            $activities[] = [
                'type' => $this->formatDonationType($donation->getDonationType()),
                'icon' => $this->getDonationIcon($donation->getDonationType(), true),
                'color' => $this->getDonationColor($donation->getDonationType(), true),
                'description' => $this->formatDonationDescription($donation, true),
                'amount' => -$donation->getAmount(),
                'date' => $donation->getTransactionDate(),
                'status' => $this->formatPaymentStatus($donation->getPaymentStatus()),
                'statusColor' => $this->getPaymentStatusColor($donation->getPaymentStatus())
            ];
        }

        // Get recent withdrawals
        $recentWithdrawals = $this->withdrawalRepository->findRecentByUser($user, 5);
        foreach ($recentWithdrawals as $withdrawal) {
            $activities[] = [
                'type' => 'Retrait',
                'icon' => 'ki-bank',
                'color' => 'warning',
                'description' => sprintf('Retrait via %s', $withdrawal->getWithdrawalMethod() === 'stripe' ? 'virement bancaire' : 'crypto'),
                'amount' => -$withdrawal->getAmount(),
                'date' => $withdrawal->getRequestedAt(),
                'status' => $this->formatWithdrawalStatus($withdrawal->getStatus()),
                'statusColor' => $this->getWithdrawalStatusColor($withdrawal->getStatus())
            ];
        }

        // Sort by date descending
        usort($activities, fn($a, $b) => $b['date'] <=> $a['date']);

        return array_slice($activities, 0, 10); // Return last 10 activities
    }

    private function formatPaymentStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            default => ucfirst($status)
        };
    }

    private function getPaymentStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-warning-100 text-warning-800',
            'completed' => 'bg-success-100 text-success-800',
            'failed' => 'bg-danger-100 text-danger-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    private function formatDonationType(string $type): string
    {
        return match ($type) {
            'registration' => 'Inscription',
            'solidarity' => 'Don Solidaire',
            'supplementary' => 'Don Supplémentaire',
            'membership' => 'Adhésion',
            default => ucfirst($type)
        };
    }

    private function getDonationIcon(string $type, bool $isDonor): string
    {
        return match ($type) {
            'registration' => $isDonor ? 'ki-user-square' : 'ki-user-tick',
            'solidarity' => 'ki-heart',
            'supplementary' => 'ki-plus',
            'membership' => 'ki-star',
            default => 'ki-gift'
        };
    }

    private function getDonationColor(string $type, bool $isDonor): string
    {
        if ($isDonor) {
            return 'warning';
        }

        return match ($type) {
            'registration' => 'info',
            'solidarity' => 'success',
            'supplementary' => 'primary',
            'membership' => 'warning',
            default => 'primary'
        };
    }

    private function formatDonationDescription(Donation $donation, bool $isDonor): string
    {


        return sprintf(
            'Donation pour %s',
            $donation->getBeneficiariesName()
        );
    }


    private function formatEarningDescription(Earning $earning): string
    {
        $otherParty = $earning->getDonor()->getDonor();

        return sprintf(
            'Donation de %s',
            $otherParty?->getFullName()
        );
    }

    private function formatWithdrawalStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'processed' => 'Traité',
            'failed' => 'Échoué',
            default => ucfirst($status)
        };
    }

    private function getWithdrawalStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-warning-100 text-warning-800',
            'processed' => 'bg-success-100 text-success-800',
            'failed' => 'bg-danger-100 text-danger-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}

