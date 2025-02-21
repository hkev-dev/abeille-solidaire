<?php

namespace App\Controller\Public;

use App\Repository\MainSliderRepository;
use App\Repository\NewsArticleRepository;
use App\Repository\TestimonialRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DonationRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'landing.home')]
    public function index(
        MainSliderRepository      $mainSliderRepository,
        ProjectCategoryRepository $categoryRepository,
        UserRepository            $userRepository,
        DonationRepository        $donationRepository,
        ProjectRepository         $projectRepository,
        NewsArticleRepository     $newsRepository,
        TestimonialRepository     $testimonialRepository
    ): Response
    {
        // Get active slides from database
        $mainSlides = $mainSliderRepository->findActiveSlides();

        // Get active categories from database
        $projectCategories = $categoryRepository->findActiveCategories();

        // Get featured projects from database (3 most funded active projects)
        $featuredProjects = $projectRepository->findActiveOrderByReceivedAmount(limit: 3);

        // Get total of donations
        $totalDonation = $donationRepository->countCompleted();

        // Get total of user
        $totalUser = $userRepository->countAll();

        // Why Choose Content
        $whyChooseContent = [
            'description' => 'Notre plateforme possède des caractéristiques uniques qui permettent à chaque membre de progresser efficacement et de développer ses projets.',
            'points' => [
                [
                    'title' => '100% de Réussite',
                    'description' => 'Un système de dons cycliques garantissant la réussite de chaque projet.'
                ],
                [
                    'title' => 'Collectons ensemble',
                    'description' => 'Une communauté solidaire qui s\'entraide pour réaliser ses projets.'
                ]
            ]
        ];

        // Get recommended projects from database
        $recommendedProjectsList = $projectRepository->findActiveOrderByReceivedAmount(limit: 4);

        // Testimonials
        $testimonialsList = $testimonialRepository->findAll();

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
            'subtitle' => 'Votre histoire commence ici',
            'title' => 'Prêt à récolter des fonds pour votre projet ?'
        ];

        // Video Section
        $videoContent = [
            'videoUrl' => 'https://www.youtube.com/watch?v=MhpYOIhOMsA',
            'videoTitle' => 'Abeille Solidaire révolutionne la façon<br>dont les individus s\'entraident' 
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
            'videoTitle' => $videoContent['videoTitle'],
            'totalDonation' => $totalDonation,
            'totalUser' => $totalUser
        ]);
    }
}
