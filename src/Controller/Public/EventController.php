<?php

namespace App\Controller\Public;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/events', name: 'landing.events.')]
class EventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private PaginatorInterface $paginator
    ) {
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
