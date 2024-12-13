<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'landing.home')]
    public function index(): Response
    {
        // Main Slider Data
        $mainSlides = [
            [
                'image' => 'landing/images/backgrounds/main-slider-1-1.jpg',
                'subtitle' => 'Raising Money is Easy Now!',
                'title' => 'Ultimate <br> Crowdfunding <br> Platforms'
            ],
            [
                'image' => 'landing/images/backgrounds/main-slider-1-2.jpg',
                'subtitle' => 'Support Amazing Ideas!',
                'title' => 'Innovative <br> Projects Need <br> Your Help'
            ],
            [
                'image' => 'landing/images/backgrounds/main-slider-1-3.jpg',
                'subtitle' => 'Make Dreams Come True!',
                'title' => 'Revolutionary <br> Crowdfunding <br> Solutions'
            ]
        ];

        // Categories Data
        $projectCategories = [
            [
                'icon' => 'icon-online',
                'name' => 'Technology'
            ],
            [
                'icon' => 'icon-skincare',
                'name' => 'Fashion'
            ],
            [
                'icon' => 'icon-photograph',
                'name' => 'Videos'
            ],
            [
                'icon' => 'icon-translation',
                'name' => 'Education'
            ],
            [
                'icon' => 'icon-design-thinking',
                'name' => 'Design'
            ],
            [
                'icon' => 'icon-patient',
                'name' => 'Medical'
            ]
        ];

        // Featured Projects Data
        $featuredProjects = [
            [
                'id' => 1,
                'image' => 'landing/images/project/project-1-1.jpg',
                'title' => 'AudioPhile – First Smart Wireless Headphones',
                'category' => 'Technology',
                'remainingDays' => 275,
                'progressPercentage' => 70,
                'achievedAmount' => 39000,
                'goalAmount' => 55000
            ],
            [
                'id' => 2,
                'image' => 'landing/images/project/project-1-2.jpg',
                'title' => 'Bourne – Travel Briefcase and Padfolio',
                'category' => 'Fashion',
                'remainingDays' => 180,
                'progressPercentage' => 80,
                'achievedAmount' => 45000,
                'goalAmount' => 60000
            ],
            [
                'id' => 3,
                'image' => 'landing/images/project/project-1-3.jpg',
                'title' => 'OfficeX – Luxury Seating for your Office',
                'category' => 'Design',
                'remainingDays' => 90,
                'progressPercentage' => 90,
                'achievedAmount' => 52000,
                'goalAmount' => 65000
            ]
        ];

        // Why Choose Content
        $whyChooseContent = [
            'description' => 'There are certain attributes of a profession and one has to catch hold of them in order to work efficiently and grow in that business.',
            'points' => [
                [
                    'title' => '100% Success Rates',
                    'description' => 'Lorem ipsum velit anod ips aliquet enean quis.'
                ],
                [
                    'title' => 'Millions in Funding',
                    'description' => 'Lorem ipsum velit anod ips aliquet enean quis.'
                ]
            ]
        ];

        // Recommended Projects
        $recommendedProjectsList = [
            [
                'id' => 4,
                'image' => 'landing/images/resources/recommended-1-1.jpg',
                'title' => 'OfficeX – Luxury Seating for your Office',
                'category' => 'Design',
                'remainingDays' => 20,
                'progressPercentage' => 70,
                'currentAmount' => 35000,
                'goalAmount' => 55000,
                'backersCount' => 12
            ],
            [
                'id' => 5,
                'image' => 'landing/images/resources/recommended-1-2.jpg',
                'title' => 'AudioPhile – First Smart Wireless Headphones',
                'category' => 'Technology',
                'remainingDays' => 20,
                'progressPercentage' => 70,
                'currentAmount' => 35000,
                'goalAmount' => 55000,
                'backersCount' => 12
            ],
            // Add more recommended projects...
        ];

        // Testimonials
        $testimonialsList = [
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'name' => 'Kevin Cooper',
                'position' => 'CO Founder',
                'rating' => 5,
                'content' => 'I tried this smart piano and learned how to play music in a day. There are many variations of passages of lorem ipsum but the majority have alteration in some form.'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sarah Albert',
                'position' => 'Marketing Director',
                'rating' => 5,
                'content' => 'Outstanding platform for crowdfunding. The process was smooth and the support team was incredibly helpful throughout the campaign.'
            ],
            // Add more testimonials...
        ];

        // Brand Partners
        $brandPartners = [
            [
                'name' => 'Brand 1',
                'image' => 'landing/images/brand/brand-1-1.png'
            ],
            [
                'name' => 'Brand 2',
                'image' => 'landing/images/brand/brand-1-2.png'
            ],
            [
                'name' => 'Brand 3',
                'image' => 'landing/images/brand/brand-1-3.png'
            ],
            // Add more brands...
        ];

        // Latest News
        $latestNews = [
            [
                'image' => 'landing/images/blog/news-1-1.jpg',
                'title' => 'Money markets rates finding the best accounts',
                'slug' => 'money-markets-rates',
                'date' => new \DateTime('2024-02-28'),
                'author' => 'Admin',
                'commentsCount' => 2
            ],
            [
                'image' => 'landing/images/blog/news-1-2.jpg',
                'title' => 'Future where technology creates good jobs',
                'slug' => 'future-technology-jobs',
                'date' => new \DateTime('2024-02-27'),
                'author' => 'Admin',
                'commentsCount' => 4
            ],
            [
                'image' => 'landing/images/blog/news-1-3.jpg',
                'title' => 'The life of entrepreneur & business co founders',
                'slug' => 'entrepreneur-life',
                'date' => new \DateTime('2024-02-26'),
                'author' => 'Admin',
                'commentsCount' => 6
            ]
        ];

        // Ready Section Content
        $readyContent = [
            'subtitle' => 'Your story starts from here',
            'title' => 'Ready to raise funds for idea?'
        ];

        // Video Section
        $videoContent = [
            'videoUrl' => 'https://www.youtube.com/watch?v=Get7rqXYrbQ',
            'videoTitle' => 'Qrowd is evolving the way<br>individuals works'
        ];

        return $this->render('public/pages/home.html.twig', [
            'mainSlides' => $mainSlides,
            'projectCategories' => $projectCategories,
            'featuredProjects' => $featuredProjects,
            'whyChooseContent' => $whyChooseContent,
            'recommendedProjectsList' => $recommendedProjectsList,
            'testimonialsList' => $testimonialsList,
            'brandPartners' => $brandPartners,
            'latestNews' => $latestNews,
            'readyContent' => $readyContent,
            'videoUrl' => $videoContent['videoUrl'],
            'videoTitle' => $videoContent['videoTitle']
        ]);
    }
}
