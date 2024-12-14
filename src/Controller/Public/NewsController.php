<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/news', name: 'landing.news.')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $news = [
            [
                'id' => 1,
                'image' => 'landing/images/blog/news-1-1.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'slug' => 'money-markets-rates'
            ],
            [
                'id' => 2,
                'image' => 'landing/images/blog/news-1-2.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'slug' => 'money-markets-rates'
            ],
            [
                'id' => 3,
                'image' => 'landing/images/blog/news-1-3.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'slug' => 'money-markets-rates'
            ],
        ];

        return $this->render('public/pages/news/index.html.twig', [
            'news' => $news
        ]);
    }

    #[Route('/details/{slug}', name: 'details')]
    public function details(string $slug): Response
    {
        $article = [
            'image' => 'landing/images/blog/news-details-img-1.jpg',
            'date' => [
                'day' => '28',
                'month' => 'july'
            ],
            'author' => 'Admin',
            'comments_count' => 2,
            'title' => 'Money markets rates finding the best accounts',
            'content' => 'There are many variations of passages of Lorem Ipsum available...',
            'tags' => ['Crowdfunding', 'Technology'],
            'comments' => [
                [
                    'author' => 'Kevin Martin',
                    'image' => 'landing/images/blog/comment-1-1.jpg',
                    'content' => 'Mauris non dignissim purus, ac commodo diam...'
                ],
                [
                    'author' => 'Sarah Albert',
                    'image' => 'landing/images/blog/comment-1-2.jpg',
                    'content' => 'Outstanding platform for crowdfunding. The process was smooth...'
                ]
            ]
        ];

        $sidebar = [
            'latest_posts' => [
                [
                    'image' => 'landing/images/blog/lp-1-1.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ],
                [
                    'image' => 'landing/images/blog/lp-1-2.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ],
                [
                    'image' => 'landing/images/blog/lp-1-3.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ]
            ],
            'categories' => [
                'Crowdfunding',
                'Charity',
                'Innovations',
                'Technology',
                'Industries'
            ],
            'tags' => [
                'Crowdfunding',
                'Technology',
                'Startup',
                'Market',
                'Lead',
                'Innovations'
            ]
        ];

        return $this->render('public/pages/news/details.html.twig', [
            'article' => $article,
            'sidebar' => $sidebar
        ]);
    }

    #[Route('/sidebar', name: 'sidebar')]
    public function sidebar(): Response
    {
        $articles = [
            [
                'slug' => 'money-markets-rates',
                'image' => 'landing/images/blog/news-sidebar-img-1.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'excerpt' => 'There are many variations of passages of Lorem Ipsum available...'
            ],
            [
                'slug' => 'money-markets-rates',
                'image' => 'landing/images/blog/news-sidebar-img-2.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'excerpt' => 'There are many variations of passages of Lorem Ipsum available...'
            ],
            [
                'slug' => 'money-markets-rates',
                'image' => 'landing/images/blog/news-sidebar-img-3.jpg',
                'date' => [
                    'day' => '28',
                    'month' => 'july'
                ],
                'author' => 'Admin',
                'comments_count' => 2,
                'title' => 'Money markets rates finding the best accounts',
                'excerpt' => 'There are many variations of passages of Lorem Ipsum available...'
            ]
        ];

        $sidebar = [
            'latest_posts' => [
                [
                    'image' => 'landing/images/blog/lp-1-1.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ],
                [
                    'image' => 'landing/images/blog/lp-1-2.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ],
                [
                    'image' => 'landing/images/blog/lp-1-3.jpg',
                    'date' => '8 May, 2022',
                    'title' => 'Do you still get benefits of crowdfunding'
                ]
            ],
            'categories' => [
                'Crowdfunding',
                'Charity',
                'Innovations',
                'Technology',
                'Industries'
            ],
            'tags' => [
                'Crowdfunding',
                'Technology',
                'Startup',
                'Market',
                'Lead',
                'Innovations'
            ]
        ];

        return $this->render('public/pages/news/sidebar.html.twig', [
            'articles' => $articles,
            'sidebar' => $sidebar
        ]);
    }
}
