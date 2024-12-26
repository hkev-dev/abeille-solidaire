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

#[Route('/news', name: 'landing.news.')]
class NewsController extends AbstractController
{
    public function __construct(
        private readonly NewsArticleRepository  $newsRepository,
        private readonly NewsCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $articles = $this->newsRepository->getPaginatedArticles(
            $request->query->getInt('page', 1),
            6 // Number of items per page
        );

        return $this->render('public/pages/news/index.html.twig', [
            'news' => $articles
        ]);
    }

    #[Route('/details/{slug}', name: 'details')]
    public function details(string $slug, Request $request): Response
    {
        $article = $this->newsRepository->findOneBy(['slug' => $slug]);

        // Add search form
        $searchForm = $this->createForm(NewsSearchType::class, null, [
            'action' => $this->generateUrl('landing.news.search')
        ]);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setArticle($article);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your comment has been added successfully.');
            return $this->redirectToRoute('landing.news.details', ['slug' => $article->getSlug()]);
        }

        $latestArticles = $this->newsRepository->findLatest(3);

        return $this->render('public/pages/news/details.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView(),
            'latestArticles' => $latestArticles,
            'searchForm' => $searchForm->createView(),
            'categories' => $this->categoryRepository->findByOrderedByName() // Add this line
        ]);
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $form = $this->createForm(NewsSearchType::class);
        $form->handleRequest($request);

        $articles = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $term = $form->get('query')->getData();
            $articles = $this->newsRepository->getPaginatedSearchResults(
                $term,
                $request->query->getInt('page', 1),
                6
            );
        }

        // Get latest articles for sidebar
        $latestArticles = $this->newsRepository->findLatest(3);
        $categories = $this->categoryRepository->findByOrderedByName();

        // Get all unique tags from articles
        $tags = [];
        if (!empty($articles)) {
            foreach ($articles as $article) {
                $tags = array_merge($tags, $article->getTags());
            }
            $tags = array_unique($tags);
        }

        return $this->render('public/pages/news/search.html.twig', [
            'searchForm' => $form->createView(),
            'articles' => $articles,
            'latestArticles' => $latestArticles,
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    #[Route('/tag/{tag}', name: 'tag')]
    public function byTag(string $tag, Request $request): Response
    {
        $articles = $this->newsRepository->getPaginatedByTag(
            $tag,
            $request->query->getInt('page', 1),
            6
        );

        // Create search form for sidebar
        $searchForm = $this->createForm(NewsSearchType::class, null, [
            'action' => $this->generateUrl('landing.news.search')
        ]);

        $latestArticles = $this->newsRepository->findLatest(3);
        $categories = $this->categoryRepository->findByOrderedByName();

        // Get all unique tags from articles
        $tags = [];
        foreach ($articles as $article) {
            $tags = array_merge($tags, $article->getTags());
        }
        $tags = array_unique($tags);

        return $this->render('public/pages/news/tag.html.twig', [
            'tag' => $tag,
            'articles' => $articles,
            'searchForm' => $searchForm->createView(),
            'latestArticles' => $latestArticles,
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    #[Route('/category/{slug}', name: 'category')]
    public function byCategory(string $slug, Request $request): Response
    {
        $category = $this->categoryRepository->findOneBySlugWithArticles($slug);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $articles = $this->paginator->paginate(
            $category->getArticles(),
            $request->query->getInt('page', 1),
            6
        );

        // Create search form for sidebar
        $searchForm = $this->createForm(NewsSearchType::class, null, [
            'action' => $this->generateUrl('landing.news.search')
        ]);

        $latestArticles = $this->newsRepository->findLatest(3);

        // Get all unique tags from the category's articles
        $tags = [];
        foreach ($articles as $article) {
            $tags = array_merge($tags, $article->getTags());
        }
        $tags = array_unique($tags);

        return $this->render('public/pages/news/category.html.twig', [
            'category' => $category,
            'articles' => $articles,
            'searchForm' => $searchForm->createView(),
            'categories' => $this->categoryRepository->findByOrderedByName(),
            'latestArticles' => $latestArticles,
            'tags' => $tags
        ]);
    }
}
