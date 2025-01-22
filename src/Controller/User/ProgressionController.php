<?php

namespace App\Controller\User;

use App\Repository\FlowerRepository;
use App\Service\MatrixService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/progression')]
class ProgressionController extends AbstractController
{
    public function __construct(
        private readonly MatrixService $matrixService,
        private readonly FlowerRepository $flowerRepository
    ) {
    }

    #[Route('/matrix', name: 'app.user.progression.matrix')]
    public function matrix(): Response
    {
        $user = $this->getUser();
        $currentFlower = $user->getCurrentFlower();

        if (!$currentFlower) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        return $this->render('user/pages/progression/matrix.html.twig', [
            'currentFlower' => $currentFlower,
            'matrix' => $this->matrixService->visualizeMatrix($currentFlower),
            'progress' => $user->getFlowerProgress(),
            'position' => $user->getMatrixPosition(),
            'totalReceived' => $user->getTotalReceived()
        ]);
    }

    #[Route('/cycles', name: 'app.user.progression.cycles')]
    public function cycles(): Response
    {
        /* @var User $user  */
        $user = $this->getUser();

        return $this->render('user/pages/progression/cycles.html.twig', [
            'completedCycles' => [],
            'totalEarned' => $user->getTotalReceived(),
            'currentFlower' => $user->getCurrentFlower(),
            'progress' => $user->getFlowerProgress()
        ]);
    }

    #[Route('/next-flower', name: 'app.user.progression.next_flower')]
    public function nextFlower(): Response
    {
        $user = $this->getUser();
        $currentFlower = $user->getCurrentFlower();

        if (!$currentFlower) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        $nextFlower = $this->flowerRepository->findNextFlower($currentFlower);

        return $this->render('user/pages/progression/next_flower.html.twig', [
            'currentFlower' => $currentFlower,
            'nextFlower' => $nextFlower,
            'requirements' => $nextFlower->getDonationAmount(),
            'progress' => $user->getFlowerProgress()
        ]);
    }
}
