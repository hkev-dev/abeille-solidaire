<?php

namespace App\Controller\Public;

use App\Repository\TestimonialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'landing.about')]
    public function index(TestimonialRepository     $testimonialRepository): Response
    {
        $testimonials = $testimonialRepository->findAll();

        $teamMembers = [
            [
                'name' => 'Michel Dubois',
                'position' => 'Consultant',
                'image' => 'team-1-1.jpg',
                'social' => [
                    'twitter' => '#',
                    'facebook' => '#',
                    'instagram' => '#'
                ]
            ],
            [
                'name' => 'Sarah Albert',
                'position' => 'Manager',
                'image' => 'team-1-2.jpg',
                'social' => [
                    'twitter' => '#',
                    'facebook' => '#',
                    'instagram' => '#'
                ]
            ],
            [
                'name' => 'Kevin Martin',
                'position' => 'Director',
                'image' => 'team-1-3.jpg',
                'social' => [
                    'twitter' => '#',
                    'facebook' => '#',
                    'instagram' => '#'
                ]
            ]
        ];

        return $this->render('public/pages/about/index.html.twig', [
            'testimonials' => $testimonials,
            'team_members' => $teamMembers
        ]);
    }
}
