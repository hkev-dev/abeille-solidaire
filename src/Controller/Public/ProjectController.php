<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects', name: 'landing.projects.')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $projects = [
            [
                'id' => 1,
                'image' => 'landing/images/project/project-1-1.jpg',
                'category' => 'Technology',
                'title' => 'AudioPhile – First Smart Wireless Headphones',
                'remainingDays' => 275,
                'progress' => 70,
                'achieved' => 39000,
                'goal' => 35000
            ],
            [
                'id' => 2,
                'image' => 'landing/images/project/project-1-2.jpg',
                'category' => 'Education',
                'title' => 'Bourne – Travel Briefcase and Padfolio',
                'remainingDays' => 275,
                'progress' => 80,
                'achieved' => 39000,
                'goal' => 35000
            ],
            [
                'id' => 3,
                'image' => 'landing/images/project/project-1-3.jpg',
                'category' => 'Design',
                'title' => 'OfficeX – Luxury Seating for your Office',
                'remainingDays' => 275,
                'progress' => 90,
                'achieved' => 39000,
                'goal' => 35000
            ],
            // Add more sample projects...
        ];

        return $this->render('public/pages/projects/index.html.twig', [
            'projects' => $projects
        ]);
    }

    #[Route('/{id}', name: 'details')]
    public function details(int $id): Response
    {
        $project = [
            'id' => $id,
            'image' => 'landing/images/project/project-details-top-img-1.jpg',
            'category' => 'Technology',
            'location' => 'ShenZhen, China',
            'title' => 'AudioPhile – First Smart Wireless Headphones',
            'pledged' => 6830,
            'backers' => 80,
            'daysLeft' => 23,
            'progress' => 70,
            'goal' => 3600,
            'description' => 'Mauris non dignissim purus, ac commodo diam. Donec sit amet lacinia nulla. Aliquam quis purus.',
            'creator' => [
                'name' => 'Kevin Martin',
                'image' => 'landing/images/project/project-details-top-person-img-1.jpg',
                'campaigns' => 2,
                'backed' => 10
            ],
            'story' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent vulputate sed mauris vitae pellentesque...',
            'faqs' => [
                [
                    'question' => 'Is my campaign allowed on qrowd?',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.'
                ],
                [
                    'question' => 'How do I get started?',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.'
                ],
                [
                    'question' => 'Can I cancel at any time?',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.'
                ]
            ],
            'updates' => [
                [
                    'time' => '20 Hours Ago',
                    'title' => 'This is the first update of our new project',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...'
                ],
                [
                    'time' => '2 Days Ago',
                    'title' => 'This is the second update of our new project',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...'
                ],
                [
                    'time' => '5 Days Ago',
                    'title' => 'This is the third update of our new project',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...'
                ]
            ],
            'reviews' => [
                [
                    'author' => 'Kevin Martin',
                    'image' => 'landing/images/project/project-details-review-img-1-1.jpg',
                    'rating' => 5,
                    'comment' => 'Mauris non dignissim purus, ac commodo diam. Donec sit amet lacinia nulla.'
                ],
                [
                    'author' => 'Sarah Albert',
                    'image' => 'landing/images/project/project-details-review-img-1-2.jpg',
                    'rating' => 4,
                    'comment' => 'Mauris non dignissim purus, ac commodo diam. Donec sit amet lacinia nulla.'
                ]

            ],
            'similarProjects' => [
                [
                    'id' => 4,
                    'image' => 'landing/images/project/project-1-1.jpg',
                    'category' => 'Technology',
                    'title' => 'AudioPhile – First Smart Wireless Headphones',
                    'remainingDays' => 275,
                    'progress' => 70,
                    'achieved' => 39000,
                    'goal' => 35000
                ],
                [
                    'id' => 5,
                    'image' => 'landing/images/project/project-1-2.jpg',
                    'category' => 'Education',
                    'title' => 'Bourne – Travel Briefcase and Padfolio',
                    'remainingDays' => 275,
                    'progress' => 80,
                    'achieved' => 39000,
                    'goal' => 35000
                ],
                [
                    'id' => 6,
                    'image' => 'landing/images/project/project-1-3.jpg',
                    'category' => 'Design',
                    'title' => 'OfficeX – Luxury Seating for your Office',
                    'remainingDays' => 275,
                    'progress' => 90,
                    'achieved' => 39000,
                    'goal' => 35000
                ],
            ]
        ];

        $project['storyContent'] = [
            'keyPoints' => [
                'Nsectetur cing mauris quis risus laoreet elit.',
                'Suspe ndisse dolor sit amet suscipit sagittis leo.',
                'Entum estibulum metus aliquam egestas dignissim posuere.',
                'If you are going to use a auctor nec purus passage.'
            ],
            'galleryImages' => [
                'main' => [
                    'landing/images/project/project-details-tab-box-story-img-one-1.jpg',
                    'landing/images/project/project-details-tab-box-story-img-one-2.jpg'
                ],
                'secondary' => 'landing/images/project/project-details-tab-box-story-img-two-1.jpg'
            ],
            'paragraphs' => [
                'Integer feugiat est in tincidunt congue. Nam eget accumsan ligula. Nunc auctor ligula a quam fermentum, non iaculis diam suscipit...',
                'Nulla in ex at mi viverra sagittis ut non erat raesent nec congue elit. Nunc arcu odio, convallis a lacinia ut...'
            ]
        ];

        return $this->render('public/pages/projects/details.html.twig', [
            'project' => $project
        ]);
    }
}
