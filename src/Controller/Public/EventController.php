<?php

namespace App\Controller\Public;

use App\Repository\EventRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', name: 'landing.events.')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository    $eventRepository,
        private readonly PaginatorInterface $paginator
    )
    {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->eventRepository->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery();

        $events = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 // Number of items per page
        );

        return $this->render('public/pages/events/index.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/{slug}', name: 'details')]
    public function details(string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);

        return $this->render('public/pages/events/details.html.twig', [
            'event' => $event
        ]);
    }
}
