<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class ContactController extends AbstractController
{
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    #[Route('/contact', name: 'landing.contact')]
    public function index(): Response
    {
        $config = $this->twig->getGlobals()['site_config'];
        
        $addresses = [
            [
                'title' => 'Adresse',
                'icon' => 'icon-location',
                'content' => $config['contact']['address']
            ],
            [
                'title' => 'Contact',
                'icon' => 'icon-contact',
                'content' => [
                    'phone' => $config['contact']['phone'],
                    'email1' => $config['contact']['email']
                ]
            ]
        ];

        return $this->render('public/pages/contact/index.html.twig', [
            'addresses' => $addresses
        ]);
    }
}
