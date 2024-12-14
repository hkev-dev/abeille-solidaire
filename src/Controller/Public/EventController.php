<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', name: 'landing.events.')]
class EventController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $events = [
            [
                'id' => 1,
                'image' => 'landing/images/events/events-page-img-1-1.jpg',
                'date' => '23 May, 2022',
                'title' => 'New small businesses saturday workshop',
                'time' => '8:00pm',
                'location' => 'New York'
            ],
            [
                'id' => 2,
                'image' => 'landing/images/events/events-page-img-1-2.jpg',
                'date' => '23 May, 2022',
                'title' => 'Taking your startup to the next level',
                'time' => '8:00pm',
                'location' => 'New York'
            ],
            [
                'id' => 3,
                'image' => 'landing/images/events/events-page-img-1-3.jpg',
                'date' => '23 May, 2022',
                'title' => 'New small businesses saturday workshop',
                'time' => '8:00pm',
                'location' => 'New York'
            ],
        ];

        return $this->render('public/pages/events/index.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $events = [
            [
                'id' => 1,
                'image' => 'landing/images/events/events-list-img-1-1.jpg',
                'date' => '23 May, 2022',
                'time' => '23 May 8:00 pm',
                'location' => 'New York',
                'title' => 'New small businesses saturday workshop',
                'description' => 'There are many variations of passages of available, but the majority have suffered alteration in some form, by injected or randomised words which don\'t look even slightly.'
            ],
            [
                'id' => 2,
                'image' => 'landing/images/events/events-list-img-1-2.jpg',
                'date' => '23 May, 2022',
                'time' => '23 May 8:00 pm',
                'location' => 'New York',
                'title' => 'Taking your startup to the next level',
                'description' => 'There are many variations of passages of available, but the majority have suffered alteration in some form, by injected or randomised words which don\'t look even slightly.'
            ],
            [
                'id' => 3,
                'image' => 'landing/images/events/events-list-img-1-3.jpg',
                'date' => '23 May, 2022',
                'time' => '23 May 8:00 pm',
                'location' => 'New York',
                'title' => 'New small businesses saturday workshop',
                'description' => 'There are many variations of passages of available, but the majority have suffered alteration in some form, by injected or randomised words which don\'t look even slightly.'
            ]
        ];

        return $this->render('public/pages/events/list.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/{id}', name: 'details')]
    public function details(int $id): Response
    {
        $event = [
            'id' => $id,
            'image' => 'landing/images/events/event-details-img-1.jpg',
            'date' => '23 May, 2022',
            'title' => 'Taking your startup to the next level',
            'content' => [
                'description' => 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text.',
                'requirements' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry...'
            ],
            'details' => [
                'time' => '8:00AM to 2:00PM',
                'date' => '23 May, 2022',
                'category' => 'Crowdfunding',
                'phone' => '92 666 888 0000',
                'email' => 'Info@event.com',
                'location' => '8 Street, San Marcos London D29, UK'
            ]
        ];

        return $this->render('public/pages/events/details.html.twig', [
            'event' => $event
        ]);
    }
}
