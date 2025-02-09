<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/detail/{id}', name: 'detail')]
    public function detail(Project $project): Response
    {
        return $this->render('admin/pages/project/detail.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(Project $project, Request $request, EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre projet a été mis à jour avec succès.');
            return $this->redirectToRoute('app.user.project.index');
        }

        return $this->render('admin/pages/project/update.html.twig', [
            'form' => $form->createView(),
            'project' => $project
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Project $project, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($project);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.project.index');
    }
}
