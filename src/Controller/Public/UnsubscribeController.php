<?php

namespace App\Controller\Public;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UnsubscribeController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('/email/unsubscribe/{token}', name: 'app_email_unsubscribe', methods: ['GET'])]
    public function unsubscribe(string $token): Response
    {
        // Validate token and find user
        // For now, just show the unsubscribe confirmation page
        
        return $this->render('emails/unsubscribe.html.twig', [
            'success' => true
        ]);
    }
}
