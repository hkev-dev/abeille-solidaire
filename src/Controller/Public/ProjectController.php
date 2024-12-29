<?php

namespace App\Controller\Public;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Project;

#[Route('/projects', name: 'landing.projects.')]
class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->projectRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $projects = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9 // Number of items per page
        );

        return $this->render('public/pages/projects/index.html.twig', [
            'projects' => $projects
        ]);
    }

    #[Route('/{slug}', name: 'details')]
    public function details(string $slug): Response
    {
        $project = $this->projectRepository->findOneBySlug($slug);

        if (!$project) {
            throw $this->createNotFoundException('Project not found');
        }

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
