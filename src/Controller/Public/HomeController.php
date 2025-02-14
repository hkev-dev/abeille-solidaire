<?php

namespace App\Controller\Public;

use App\Repository\MainSliderRepository;
use App\Repository\NewsArticleRepository;
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
        DonationRepository        $donationRepository,
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

        // Get total of donations
        $totalDonation = $donationRepository->countCompleted()

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
        $recommendedProjectsList = $projectRepository->findActive();

        // Testimonials
        $testimonialsList = [
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'name' => 'Kevin Cooper',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Grâce à Abeilles Solidaires, j\'ai pu financer mon projet en quelques semaines. La communauté est très active et le système de dons cycliques fonctionne parfaitement.'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sarah Albert',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Une plateforme exceptionnelle pour le financement participatif. Le processus est simple et l\'équipe de support est incroyablement réactive tout au long du parcours.'
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
            'subtitle' => 'Votre histoire commence ici',
            'title' => 'Prêt à récolter des fonds pour votre projet ?'
        ];

        // Video Section
        $videoContent = [
            'videoUrl' => 'https://www.youtube.com/watch?v=ToRJ1pkr7WQ',
            'videoTitle' => 'Abeilles Solidaires révolutionne la façon<br>dont les individus s\'entraident'
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
            'totalDonation' => $totalDonation
        ]);
    }
}
