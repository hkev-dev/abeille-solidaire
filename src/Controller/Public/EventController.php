<?php

namespace App\Controller\Public;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', name: 'landing.events.')]
class EventController extends AbstractController
{
    public function __construct(private EventRepository $eventRepository)
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $events = $this->eventRepository->findUpcoming();

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
