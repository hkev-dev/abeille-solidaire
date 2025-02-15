<?php

namespace App\Controller\Public;

use App\Repository\MainSliderRepository;
use App\Repository\NewsArticleRepository;
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
        $recommendedProjectsList = $projectRepository->findActive();

        // Testimonials
        $testimonialsList = [
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'name' => 'Kevin H.',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Grâce à Abeille Solidaire, j\'ai pu financer mon projet en quelques semaines. La communauté est très active et le système de dons cycliques fonctionne parfaitement.'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sarah Lelouche',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Une plateforme exceptionnelle pour le financement participatif. Le processus est simple et l\'équipe de support est incroyablement réactive tout au long du parcours.'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-1.jpg',
                'name' => 'Kevin Cooper',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Rejoindre Abeille Solidaire, c\'est donner du sens à ses actions. Ensemble, créons des projets qui font la différence !'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sarah Albert',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Un petit geste, un grand impact ! Venez découvrir un club où la solidarité rime avec créativité.'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Gabriel',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Ici, chaque idée compte et chaque don fait avancer des projets inspirants. Rejoignez-nous !'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Raphaël',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Vous avez envie d\’aider et d\’innover ? Abeille Solidaire est fait pour vous '
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Noah',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Plus qu\’un club, une famille engagée pour un monde meilleur. Faites partie de l\’aventure !'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sacha',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Abeille Solidaire, c\’est du partage, du soutien et des projets concrets. On vous attend !'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sacha',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Ensemble, nous bâtissons des initiatives qui changent des vies. Venez voir par vous-même !'
            ],
            [
                'image' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'thumbnail' => 'landing/images/testimonial/testimonial-one-client-img-2.jpg',
                'name' => 'Sacha',
                'position' => 'Abeille',
                'rating' => 5,
                'content' => 'Votre générosité a du pouvoir. Chez Abeille Solidaire, elle devient action!'
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
