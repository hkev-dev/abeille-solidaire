<?php

namespace App\Controller\User;

use App\Service\FlowerProgressionService;
use App\Service\MatrixPlacementService;
use App\Repository\FlowerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/progression')]
class ProgressionController extends AbstractController
{
    public function __construct(
        private readonly FlowerProgressionService $progressionService,
        private readonly MatrixPlacementService $matrixPlacementService,
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
            'matrix' => $this->matrixPlacementService->visualizeMatrix($currentFlower),
            'progress' => $user->getFlowerProgress(),
            'position' => $this->progressionService->getCurrentPosition($user),
            'totalReceived' => $this->progressionService->getTotalReceivedInCurrentFlower($user),
            'referrals' => $user->getReferrals()->toArray() // Add this line to pass referrals to template
        ]);
    }

    #[Route('/cycles', name: 'app.user.progression.cycles')]
    public function cycles(): Response
    {
        $user = $this->getUser();

        return $this->render('user/pages/progression/cycles.html.twig', [
            'completedCycles' => $this->progressionService->getAllCompletedCycles($user),
            'totalEarned' => $this->progressionService->getTotalEarned($user),
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
            'requirements' => $this->progressionService->getNextFlowerRequirements($user),
            'progress' => $user->getFlowerProgress(),
            'referrals' => $this->progressionService->getReferralsInNextFlower($user, $nextFlower)
        ]);
    }
}
