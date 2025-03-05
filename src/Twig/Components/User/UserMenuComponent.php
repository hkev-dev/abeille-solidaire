<?php
namespace App\Twig\Components\User;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use App\Service\UserService;
use App\Service\WalletService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('user:menu', template: 'user/components/user_menu.html.twig')]
class UserMenuComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly Security           $security,
        private readonly DonationRepository $donationRepository,
        private readonly ProjectRepository  $projectRepository,
        private readonly FlowerRepository   $flowerRepository,
        private readonly UserRepository     $userRepository,
        private readonly WalletService      $walletService,
        private readonly UserService $userService,
    ) {
    }

    #[ExposeInTemplate]
    public function getCurrentUser(): User
    {
        return $this->security->getUser();
    }

    #[ExposeInTemplate]
    public function getCurrentFlower(): ?Flower
    {
        return $this->getCurrentUser()->getCurrentFlower();
    }

    #[ExposeInTemplate]
    public function getFlowerProgress(): array
    {
        $user = $this->getCurrentUser();
        return $user->getFlowerProgress();
    }

    #[ExposeInTemplate]
    public function getWalletBalance(): float
    {
        return $this->walletService->getWalletBalance($this->getCurrentUser());
    }

    #[ExposeInTemplate]
    public function getUnreadDonations(): array
    {
        return $this->donationRepository->findRecentByUser(
            $this->getCurrentUser(),
            10
        );
    }

    #[ExposeInTemplate]
    public function hasCompletedProject(): bool
    {
        return count($this->projectRepository->findCompletedByUser($this->getCurrentUser())) > 0;
    }

    #[ExposeInTemplate]
    public function getMembershipInfo(): array
    {
        /** @var User $user */
        $user = $this->getCurrentUser();
        return [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getMembershipExpiredAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];
    }

    #[ExposeInTemplate]
    public function getWithdrawalEligibility(): array
    {
        $user = $this->getCurrentUser();
        return [
            'isEligible' => $this->userService->isEligibleForWithdrawal($user),
            'hasKyc' => $user->isKycVerified(),
            'hasAnnualFee' => $user->hasPaidAnnualFee(),
            'hasProject' => $user->hasProject(),
            'hasMinimumMatrixDepth' => $user->getMainDonation()->getMatrixDepth() >= 3
        ];
    }

    #[ExposeInTemplate]
    public function getMatrixInfo(): array
    {
        $user = $this->getCurrentUser();
        $currentFlower = $user->getCurrentFlower();

        return [
            'depth' => $user->getMainDonation()->getMatrixDepth(),
            'position' => $user->getMainDonation()->getMatrixPosition(),
            'childrenCount' => $user->getMainDonation()->getMatrixChildrenCount(),
            'totalReceived' => $currentFlower ? $user->getTotalReceivedInFlower() : 0,
            'currentCycleReceived' => $currentFlower ? $user->getTotalReceivedInCurrentCycle() : 0
        ];
    }

    #[ExposeInTemplate]
    public function getTotalDonationsReceived(): float
    {
        return $this->donationRepository->getTotalReceivedByUser($this->getCurrentUser());
    }

    #[ExposeInTemplate]
    public function getTotalDonationsMade(): float
    {
        return $this->donationRepository->getTotalMadeByUser($this->getCurrentUser());
    }

    #[ExposeInTemplate]
    public function getUserProject(): ?Project
    {
        return $this->getCurrentUser()->getCurrentProject();
    }
}