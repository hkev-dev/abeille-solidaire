<?php
namespace App\Twig\Components\User;

use App\Repository\UserRepository;
use App\Repository\DonationRepository;
use App\Repository\FlowerRepository;
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
    public function getFlowerProgress(): array
    {
        $user = $this->security->getUser();
        return $this->donationRepository->getCurrentFlowerProgress($user);
    }

    #[ExposeInTemplate]
    public function getWalletBalance(): float
    {
        return $this->security->getUser()->getWalletBalance();
    }

    #[ExposeInTemplate]
    public function getReferralCount(): int
    {
        return $this->security->getUser()->getReferrals()->count();
    }

    #[ExposeInTemplate]
    public function getUnreadDonationsCount(): int
    {
        $user = $this->security->getUser();
        return count($this->donationRepository->findRecentByUser($user, 10));
    }

    #[ExposeInTemplate]
    public function getCurrentMembership(): ?object
    {
        return $this->security->getUser()->getCurrentMembership();
    }

    #[ExposeInTemplate]
    public function getKycStatus(): bool
    {
        return $this->security->getUser()->isKycVerified();
    }

    #[ExposeInTemplate]
    public function getCompletedCycles(): int
    {
        $user = $this->security->getUser();
        $currentFlower = $user->getCurrentFlower();
        return $currentFlower ? $user->getFlowerCompletionCount($currentFlower) : 0;
    }
}