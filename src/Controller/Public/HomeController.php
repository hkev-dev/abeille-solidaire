<?php

namespace App\Controller\Public;

use App\Repository\MainSliderRepository;
use App\Repository\NewsArticleRepository;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'landing.home')]
    public function index(
        MainSliderRepository      $mainSliderRepository,
        ProjectCategoryRepository $categoryRepository,
        ProjectRepository         $projectRepository,
        NewsArticleRepository     $newsRepository
    ): Response
    {
        // Get active slides from database
        $mainSlides = $mainSliderRepository->findActiveSlides();

        // Get active categories from database
        $projectCategories = $categoryRepository->findActiveCategories();

        // Get featured projects from database (3 most funded active projects)
        $featuredProjects = $projectRepository->findActive();

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

        // Get recommended projects from database
        $recommendedProjectsList = $projectRepository->findActive();

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

        // Get latest news from database
        $latestNews = $newsRepository->findLatest();

        // Ready Section Content
        $readyContent = [
            'subtitle' => 'Your story starts from here',
            'title' => 'Ready to raise funds for idea?'
        ];

        // Video Section
        $videoContent = [
            'videoUrl' => 'https://www.youtube.com/watch?v=Get7rqXYrbQ',
            'videoTitle' => 'Abeille Solidaire is evolving the way<br>individuals works'
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
