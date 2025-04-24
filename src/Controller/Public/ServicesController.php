<?php

namespace App\Controller\Public;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\NewsSearchType;
use App\Repository\NewsArticleRepository;
use App\Repository\NewsCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/services', name: 'landing.services.')]
class ServicesController extends AbstractController
{
    public function __construct(
        private readonly NewsArticleRepository  $newsRepository,
        private readonly NewsCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface     $paginator
    )
    {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {

        return $this->render('public/pages/services/index.html.twig');
    }
}
