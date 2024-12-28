<?php

namespace App\Controller\Public;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Project;

#[Route('/projects', name: 'landing.projects.')]
class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $projects = $this->projectRepository->findAll();

        return $this->render('public/pages/projects/index.html.twig', [
            'projects' => $projects
        ]);
    }

    #[Route('/{id}', name: 'details')]
    public function details(Project $project): Response
    {
        // Using ParamConverter to automatically fetch the project

        // Get similar projects (same category, excluding current project)
        $similarProjects = $this->projectRepository->findBy(
            ['category' => $project->getCategory()],
            ['createdAt' => 'DESC'],
            3
        );

        // Filter out the current project from similar projects
        $similarProjects = array_values(array_filter($similarProjects, function ($p) use ($project) {
            return $p->getId() !== $project->getId();
        }));

        return $this->render('public/pages/projects/details.html.twig', [
            'project' => $project,
            'similarProjects' => $similarProjects
        ]);
    }
}
