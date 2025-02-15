<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Withdrawal;
use App\Repository\DonationRepository;
use App\Repository\ProjectRepository;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/withdrawal', name: 'app.admin.withdrawal.')]
#[IsGranted('ROLE_ADMIN')]
class WithdrawalController extends AbstractController
{
    #[Route('/request', name: 'request')]
    public function request(Request $request, WithdrawalRepository $withdrawalRepository, PaginatorInterface $paginator): Response
    {
        $query = $withdrawalRepository->createQueryBuilder('withdrawal')
            ->orderBy('withdrawal.updatedAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/withdrawal/request.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/request/{id}/update-status', name: 'request.update_status', methods: 'POST')]
    public function requestValidate(Request $request, Withdrawal $withdrawal, EntityManagerInterface $entityManager): Response
    {
        $newStatus= $request->request->get('status');

        if (!in_array($newStatus, Withdrawal::VALID_STATUS)) {
            $this->addFlash('error', 'Invalid status');
            return $this->redirectToRoute('app.admin.withdrawal.request');
        }

        $withdrawal->setStatus($newStatus);
        $entityManager->persist($withdrawal);
        $entityManager->flush();

        $this->addFlash('success', 'Status mis à jour avec succès');

        return $this->redirectToRoute('app.admin.withdrawal.request');
    }

    #[Route('/charge', name: 'charge')]
    public function charge(Request $request, WithdrawalRepository $withdrawalRepository, PaginatorInterface $paginator): Response
    {
        $query = $withdrawalRepository->createQueryBuilder('withdrawal')
            ->orderBy('withdrawal.updatedAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/withdrawal/charge.html.twig', [
            'pagination' => $pagination
        ]);
    }
}
