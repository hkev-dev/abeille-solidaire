<?php

namespace App\Controller\Public;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects', name: 'landing.projects.')]
class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->projectRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ;

        if ($request->query->has('category')) {
            $query->andWhere('p.category = :category')
                ->setParameter('category', $request->query->get('category'));
        }

        if ($request->query->has('q')) {
            $query->andWhere('LOWER(p.title) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $request->query->get('q') . '%');
        }

        $projects = $this->paginator->paginate(
            $query->getQuery(),
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
        /** @var Project $project */
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
        $similarProjects = array_values(array_filter($similarProjects, function (Project $p) use ($project) {
            return $p->id !== $project->id;
        }));

        return $this->render('public/pages/projects/details.html.twig', [
            'project' => $project,
            'similarProjects' => $similarProjects
        ]);
    }
}
