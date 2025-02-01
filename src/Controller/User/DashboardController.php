<?php

namespace App\Controller\User;

use App\Entity\Donation;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\DonationRepository;
use App\Repository\WithdrawalRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly UserRepository $userRepository,
        private readonly WithdrawalRepository $withdrawalRepository,
    ) {
    }

    #[Route('/user/dashboard', name: 'app.user.dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get current flower first
        $currentFlower = $user->getCurrentFlower();

        // Early return if no flower is assigned yet
        if (!$currentFlower) {
            throw $this->createAccessDeniedException('No flower assigned yet.');
        }

        // Calculate flower progress based on direct children count
        $flowerProgress = [
            'received' => $user->getMatrixChildrenCount(),
            'total' => $user->getCurrentFlower()->getMatrixRemainingSlots(),
            'percentage' => 0,
            'remainingSlots' => max(0, $user->getCurrentFlower()->getMatrixRemainingSlots() - $user->getMatrixChildrenCount())
        ];
        $flowerProgress['percentage'] = ($flowerProgress['received'] / $flowerProgress['total']) * 100;

        // Get membership info
        $membershipInfo = [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getMembershipExpiredAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];

        // Calculate withdrawal eligibility
        $canWithdraw = $user->isEligibleForWithdrawal();

        // Format recent activities
        $recentActivity = $this->formatRecentActivity($user);

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
            'recentActivity' => $recentActivity,
            'canWithdraw' => $canWithdraw && $user->getWalletBalance() >= 50.0,
        ];

        return $this->render('user/pages/dashboard/index.html.twig', $data);
    }

    private function formatRecentActivity(User $user): array
    {
        $activities = [];

        // Get recent donations (received and made)
        $recentDonations = $this->donationRepository->findRecentByUser($user, 10);
        foreach ($recentDonations as $donation) {
            $isDonor = $donation->getDonor() === $user;
            $activities[] = [
                'type' => $this->formatDonationType($donation->getDonationType()),
                'icon' => $this->getDonationIcon($donation->getDonationType(), $isDonor),
                'color' => $this->getDonationColor($donation->getDonationType(), $isDonor),
                'description' => $this->formatDonationDescription($donation, $isDonor),
                'amount' => $isDonor ? -$donation->getAmount() : $donation->getAmount(),
                'date' => $donation->getTransactionDate(),
                'status' => $this->formatPaymentStatus($donation->getPaymentStatus()),
                'statusColor' => $this->getPaymentStatusColor($donation->getPaymentStatus())
            ];

            foreach ($donation->getChildrens() as $child) {
                $isDonor = $child->getDonor() === $user;
                $activities[] = [
                    'type' => $this->formatDonationType($child->getDonationType()),
                    'icon' => $this->getDonationIcon($child->getDonationType(), $isDonor),
                    'color' => $this->getDonationColor($child->getDonationType(), $isDonor),
                    'description' => $this->formatDonationDescription($child, $isDonor),
                    'amount' => $isDonor ? -$child->getAmount() : $child->getAmount(),
                    'date' => $child->getTransactionDate(),
                    'status' => $this->formatPaymentStatus($child->getPaymentStatus()),
                    'statusColor' => $this->getPaymentStatusColor($child->getPaymentStatus())
                ];
            }
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
        $otherParty = $isDonor ? $donation->getRecipient() : $donation->getDonor();
        $action = $isDonor ? 'à' : 'de';

        return sprintf(
            '%s %s %s',
            $this->formatDonationType($donation->getDonationType()),
            $action,
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

