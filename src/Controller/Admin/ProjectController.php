<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\DonationRepository;
use App\Repository\ProjectRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/project', name: 'app.admin.project.')]
#[IsGranted('ROLE_ADMIN')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ProjectRepository $projectRepository, PaginatorInterface $paginator): Response
    {
        $query = $projectRepository->createQueryBuilder('project')
            ->orderBy('project.updatedAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/pages/project/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
}
