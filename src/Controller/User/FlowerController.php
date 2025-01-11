<?php

namespace App\Controller\User;

use App\Repository\DonationRepository;
use App\Repository\FlowerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FlowerController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/user/flower/current', name: 'app.user.flower.current')]
    public function current(): Response
    {
        $user = $this->getUser();
        $currentFlower = $user->getCurrentFlower();

        if (!$currentFlower) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        $allFlowers = array_map(function($flower) use ($currentFlower) {
            return [
                'id' => $flower->getId(),
                'name' => $flower->getName(),
                'donationAmount' => $flower->getDonationAmount(),
                'isActive' => $flower === $currentFlower,
                'isCompleted' => $flower->getLevel() < $currentFlower->getLevel()
            ];
        }, $this->flowerRepository->findBy([], ['level' => 'ASC']));

        $data = [
            'flower' => $currentFlower,
            'allFlowers' => $allFlowers,
            'progress' => $user->getFlowerProgress(),
            'matrixPositions' => $this->flowerRepository->getMatrixPositions($currentFlower),
            'completedCycles' => $user->getFlowerCompletionCount($currentFlower),
            'recentDonations' => $this->donationRepository->findByFlowerAndRecipient($currentFlower, $user, 5),
            'totalReceivedInFlower' => $this->donationRepository->getTotalReceivedInFlower($user, $currentFlower),
            'userPosition' => $this->donationRepository->getUserPositionInFlower($user, $currentFlower),
            'referralsInFlower' => $this->userRepository->findByReferrerAndFlower($user, $currentFlower),
            'recentActivity' => $this->donationRepository->findByFlowerWithActivity($currentFlower, 10),
        ];

        return $this->render('user/pages/flower/current.html.twig', $data);
    }
}
