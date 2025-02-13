<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'landing.about')]
    public function index(): Response
    {
        $testimonials = [
            [
                'name' => 'Sarah Albert',
                'position' => 'Abeillee',
                'image' => 'testimonial-two-client-img-1.jpg',
                'text' => 'Grâce à Abeilles Solidaires, notre communauté grandit chaque jour. Le système de dons cycliques permet à chacun de réaliser ses projets dans un esprit de solidarité.',
                'rating' => 5
            ],
            [
                'name' => 'Kevin Martin',
                'position' => 'Abeille',
                'image' => 'testimonial-two-client-img-2.jpg',
                'text' => 'Une plateforme innovante qui m\'a permis de concrétiser mon projet. La communauté est bienveillante et le support technique est toujours présent.',
                'rating' => 5
            ],
            [
                'name' => 'Kevin Coper',
                'position' => 'Abeille',
                'image' => 'testimonial-two-client-img-3.jpg',
                'text' => 'Exercitation ullamco laboris nisi ut aliquip ex ea ex commodo consequat duis aute aboris nisi ut aliquip irure reprehederit in voluptate velit esse.',
                'rating' => 5
            ],
            [
                'name' => 'Jessica Brown',
                'position' => 'Abeille',
                'image' => 'testimonial-two-client-img-4.jpg',
                'text' => 'Exercitation ullamco laboris nisi ut aliquip ex ea ex commodo consequat duis aute aboris nisi ut aliquip irure reprehederit in voluptate velit esse.',
                'rating' => 5
            ]
        ];

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
