<?php

namespace App\Repository;

use App\Entity\NewsArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

class NewsArticleRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, NewsArticle::class);
        $this->paginator = $paginator;
    }

    public function findLatest(int $limit = 3): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.title LIKE :term')
            ->orWhere('n.content LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPaginatedArticles(int $page = 1, int $limit = 9): PaginationInterface
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC');

        return $this->paginator->paginate($qb, $page, $limit);
    }

    public function findByTag(string $tag): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('JSON_CONTAINS(n.tags, :tag) = 1')
            ->setParameter('tag', json_encode($tag))
            ->orderBy('n.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
