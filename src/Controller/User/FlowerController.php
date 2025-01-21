<?php

namespace App\Controller\User;

use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        /** @var User $user */
        $user = $this->getUser();
        $currentFlower = $user->getCurrentFlower();

        if (!$currentFlower) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // Get matrix positions with user data
        $matrixPositions = array_map(function($position) use ($user) {
            return [
                'userId' => $position['user_id'],
                'firstName' => $position['first_name'],
                'lastName' => $position['last_name'],
                'position' => $position['matrix_position'],
                'depth' => $position['matrix_depth'],
                'isCurrentUser' => $position['user_id'] === $user->getId(),
                'isChild' => $position['parent_id'] === $user->getId(),
            ];
        }, $this->userRepository->getMatrixPositionsForFlower($currentFlower));

        $data = [
            'flower' => $currentFlower,
            'allFlowers' => $this->getFlowerProgression($currentFlower),
            'progress' => $user->getFlowerProgress(),
            'matrixPositions' => $matrixPositions,
            'completedCycles' => $user->getFlowerCompletionCount($currentFlower),
            'recentDonations' => $this->donationRepository->findByFlowerAndRecipient($currentFlower, $user, 5),
            'totalReceivedInFlower' => $this->donationRepository->getTotalReceivedInFlower($user, $currentFlower),
            'userPosition' => $user->getMatrixPosition(),
            'userDepth' => $user->getMatrixDepth(),
            'recentActivity' => $this->donationRepository->findByFlowerWithActivity($currentFlower, 10),
        ];

        return $this->render('user/pages/flower/current.html.twig', $data);
    }

    private function getFlowerProgression(Flower $currentFlower): array
    {
        return array_map(
            fn(Flower $flower) => [
                'id' => $flower->getId(),
                'name' => $flower->getName(),
                'donationAmount' => $flower->getDonationAmount(),
                'isActive' => $flower === $currentFlower,
                'isCompleted' => $flower->getDonationAmount() < $currentFlower->getDonationAmount()
            ],
            $this->flowerRepository->findAll()
        );
    }
}
