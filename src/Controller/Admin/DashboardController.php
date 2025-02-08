<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app.admin.dashboard')]
    public function index(Session $session): Response
    {
        $justLoggedIn = $session->get('justLoggedIn', false);
        $session->remove('justLoggedIn');
        return $this->render('admin/pages/dashboard/index.html.twig', [
            'showChoiceModal' => $justLoggedIn
        ]);
    }
}
