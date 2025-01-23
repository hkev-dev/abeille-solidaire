<?php
namespace App\Twig\Components\User;

use App\Entity\User;
use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('user:menu', template: 'user/components/user_menu.html.twig')]
class UserMenuComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly Security $security,
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository,
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
        return $this->getCurrentUser()->getWalletBalance();
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
    public function getMembershipInfo(): array
    {
        $user = $this->getCurrentUser();
        return [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getAnnualFeeExpiresAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];
    }

    #[ExposeInTemplate]
    public function getWithdrawalEligibility(): array
    {
        $user = $this->getCurrentUser();
        return [
            'isEligible' => $user->isEligibleForWithdrawal(),
            'hasKyc' => $user->isKycVerified(),
            'hasAnnualFee' => $user->hasPaidAnnualFee(),
            'hasProject' => $user->hasProject(),
            'hasMinimumMatrixDepth' => $user->getMatrixDepth() >= 3
        ];
    }

    #[ExposeInTemplate]
    public function getMatrixInfo(): array
    {
        $user = $this->getCurrentUser();
        $currentFlower = $user->getCurrentFlower();
        
        return [
            'depth' => $user->getMatrixDepth(),
            'position' => $user->getMatrixPosition(),
            'childrenCount' => $user->getMatrixChildrenCount(),
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
        return $this->getCurrentUser()->getProject();
    }
}