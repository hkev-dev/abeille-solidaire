<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FAQController extends AbstractController
{
    #[Route('/faq', name: 'landing.faq')]
    public function index(): Response
    {
        $faqCategories = [
            [
                'icon' => 'icon-handshake',
                'title' => 'Backers'
            ],
            [
                'icon' => 'icon-coins',
                'title' => 'Compaigns'
            ],
            [
                'icon' => 'icon-bonus',
                'title' => 'Payments'
            ],
            [
                'icon' => 'icon-entrepreneur',
                'title' => 'Entrepreneur'
            ],
            [
                'icon' => 'icon-fingerprint-scan',
                'title' => 'Legal'
            ],
            [
                'icon' => 'icon-account-1',
                'title' => 'Account'
            ]
        ];

        $faqs = [
            'group1' => [
                [
                    'question' => 'Is my campaign allowed on qrowd?',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.',
                    'active' => false
                ],
                [
                    'question' => 'How to soft launch your campaign',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.',
                    'active' => true
                ],
                // Add more FAQs...
            ],
            'group2' => [
                [
                    'question' => 'How to soft launch your campaign',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.',
                    'active' => false
                ],
                [
                    'question' => 'Is my campaign allowed on qrowd?',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.',
                    'active' => true
                ],
                [
                    'question' => 'How to soft launch your campaign',
                    'answer' => 'There are many variations of passages the majority have suffered alteration in some fo injected humour, or randomised words believable.',
                    'active' => false
                ]
            ]
        ];

        return $this->render('public/pages/faq/index.html.twig', [
            'categories' => $faqCategories,
            'faqs' => $faqs
        ]);
    }
}
