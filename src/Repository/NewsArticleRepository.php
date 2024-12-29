<?php

namespace App\Repository;

use App\Entity\NewsArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

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
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.title) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.content) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.excerpt) LIKE LOWER(:term)')
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPaginatedArticles(int $page = 1, int $limit = 9): PaginationInterface
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC');

        return $this->paginator->paginate($qb, $page, $limit);
    }

    public function getPaginatedSearchResults(string $term, int $page = 1, int $limit = 6): PaginationInterface
    {
        $qb = $this->createQueryBuilder('a')
            ->where('LOWER(a.title) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.content) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.excerpt) LIKE LOWER(:term)')
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->orderBy('a.createdAt', 'DESC');

        return $this->paginator->paginate($qb, $page, $limit);
    }

    /**
     * @throws Exception
     */
    public function getPaginatedByTag(string $tag, int $page = 1, int $limit = 6): PaginationInterface
    {
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();

        if ($platform === 'postgresql') {
            // PostgreSQL: Use raw SQL for JSON containment
            $sql = 'SELECT n.* FROM news_article n WHERE n.tags::jsonb @> :tags';
            $result = $conn->executeQuery($sql, ['tags' => json_encode([$tag])]);
            $ids = array_column($result->fetchAllAssociative(), 'id');

            $qb = $this->createQueryBuilder('a')
                ->where('a.id IN (:ids)')
                ->setParameter('ids', $ids ?: [0]) // Prevent empty IN clause
                ->orderBy('a.createdAt', 'DESC');
        } else {
            // MySQL version
            $qb = $this->createQueryBuilder('a')
                ->where("JSON_CONTAINS(a.tags, :tag) = 1")
                ->setParameter('tag', json_encode($tag))
                ->orderBy('a.createdAt', 'DESC');
        }

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }
}
