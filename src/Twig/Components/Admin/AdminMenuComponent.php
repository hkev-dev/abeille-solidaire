<?php
namespace App\Twig\Components\Admin;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Project;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use App\Repository\WithdrawalRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('admin:menu', template: 'admin/components/menu.html.twig')]
class AdminMenuComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly Security $security,
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository,
        private readonly WithdrawalRepository $withdrawalRepository,
    ) {
    }

    #[ExposeInTemplate]
    public function getCurrentUser(): User
    {
        return $this->security->getUser();
    }

    #[ExposeInTemplate]
    public function getWithdrawalPendingAmount(): float
    {
        return $this->withdrawalRepository->getTotalPendingAmount() ?? 0.0;
    }

}