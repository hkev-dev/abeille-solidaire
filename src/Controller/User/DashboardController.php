<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/user/dashboard', name: 'app.user.dashboard')]
    public function index(): Response
    {
        return $this->render('user/pages/dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
