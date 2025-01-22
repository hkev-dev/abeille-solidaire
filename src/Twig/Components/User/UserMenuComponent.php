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
    public function getCurrentFlower(): ?Flower
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user->getCurrentFlower();
    }

    #[ExposeInTemplate]
    public function getFlowerProgress(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentFlower = $user->getCurrentFlower();
        
        $received = $this->donationRepository->countByRecipientAndFlower($user, $currentFlower);
        return [
            'received' => $received,
            'total' => 4,
            'percentage' => $currentFlower ? ($received / 4 * 100) : 0
        ];
    }

    #[ExposeInTemplate]
    public function getWalletBalance(): float
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user->getWalletBalance();
    }

    #[ExposeInTemplate]
    public function getUnreadDonationsCount(): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return count($this->donationRepository->findRecentByUser($user, 10));
    }

    #[ExposeInTemplate]
    public function getMembershipInfo(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return [
            'isActive' => $user->hasPaidAnnualFee(),
            'expiresAt' => $user->getAnnualFeeExpiresAt(),
            'daysUntilExpiration' => $user->getDaysUntilAnnualFeeExpiration()
        ];
    }

    #[ExposeInTemplate]
    public function getKycStatus(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user->isKycVerified();
    }

    #[ExposeInTemplate]
    public function getCurrentFlowerData(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentFlower = $user->getCurrentFlower();
        
        if (!$currentFlower) {
            return [
                'name' => 'Aucune',
                'amount' => 0,
                'level' => 0
            ];
        }

        return [
            'name' => $currentFlower->getName(),
            'amount' => $currentFlower->getDonationAmount(),
            'level' => $currentFlower->getLevel()
        ];
    }

    #[ExposeInTemplate]
    public function getMatrixInfo(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return [
            'depth' => $user->getMatrixDepth(),
            'position' => $user->getMatrixPosition(),
            'children' => $user->getChildren()->count()
        ];
    }

    #[ExposeInTemplate]
    public function getCompletedCycles(): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentFlower = $user->getCurrentFlower();
        
        if (!$currentFlower) {
            return 0;
        }

        $cycleInfo = $this->donationRepository->getCurrentCycleInfo($user, $currentFlower);
        return $cycleInfo['totalCompletedCycles'];
    }
}