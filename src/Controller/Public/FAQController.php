<?php

namespace App\Controller\Public;

use App\Repository\CategoryRepository;
use App\Repository\FAQRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FAQController extends AbstractController
{
    #[Route('/faq', name: 'landing.faq')]
    public function index(Request $request, CategoryRepository $categoryRepository, FAQRepository $faqRepository): Response
    {
        $query = $request->query->get('q');
        $categories = $categoryRepository->findActiveCategories();

        $allFaqs = $faqRepository->findActiveFAQs();
        $searchResults = $query ? $faqRepository->searchFAQs($query) : [];

        $groupedFaqs = [
            'group1' => array_slice($allFaqs, 0, ceil(count($allFaqs) / 2)),
            'group2' => array_slice($allFaqs, ceil(count($allFaqs) / 2))
        ];

        return $this->render('public/pages/faq/index.html.twig', [
            'categories' => $categories,
            'faqs' => $groupedFaqs,
            'searchQuery' => $query,
            'searchResults' => array_map(fn($faq) => $faq->getId(), $searchResults)
        ]);
    }
}
