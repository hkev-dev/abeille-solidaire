<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'landing.contact')]
    public function index(): Response
    {
        $addresses = [
            [
                'title' => 'About',
                'icon' => 'icon-entrepreneur-1',
                'content' => 'Morbi ut tellus ac leo mol <br> stie luctus nec vehicula sed <br>
                                justo
                                onecpat ras lorem.'
            ],
            [
                'title' => 'Address',
                'icon' => 'icon-location',
                'content' => '68 Road Broklyn Street. <br> New York. United States of <br>
                                America'
            ],
            [
                'title' => 'Contact',
                'icon' => 'icon-contact',
                'content' => [
                    'phone' => '+92 ( 8800 ) - 6780',
                    'email1' => 'needhelp@qrowd.com',
                    'email2' => 'info@qrowd.com'
                ]
            ]
        ];

        return $this->render('public/pages/contact/index.html.twig', [
            'addresses' => $addresses
        ]);
    }
}
