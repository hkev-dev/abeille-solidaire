<?php

namespace App\Controller\Public;

use App\Repository\ServiceRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/services', name: 'landing.services.')]
class ServicesController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly PaginatorInterface     $paginator
    )
    {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->serviceRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ;

        /*if ($request->query->has('category')) {
            $query->andWhere('p.category = :category')
                ->setParameter('category', $request->query->get('category'));
        }*/

        if ($request->query->has('q')) {
            $query->andWhere('LOWER(p.title) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $request->query->get('q') . '%');
        }

        $services = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            9 // Number of items per page
        );

        return $this->render('public/pages/service/index.html.twig', [
            'services' => $services
        ]);
    }
}
