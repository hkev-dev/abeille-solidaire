<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ErrorController extends AbstractController
{
    #[Route('/404', name: 'landing.error.404')]
    public function notFound(): Response
    {
        return $this->render('public/pages/error/404.html.twig');
    }
}
