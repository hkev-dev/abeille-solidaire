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

    public function findByTag(string $tag): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();

        $qb = $this->createQueryBuilder('a');

        if ($platform === 'postgresql') {
            // Use native query for PostgreSQL
            $sql = 'SELECT a.* FROM news_article a WHERE a.tags::jsonb @> :tags';
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery(['tags' => json_encode([$tag])]);

            return array_map(
                fn(array $data) => $this->getEntityManager()->getRepository(NewsArticle::class)->find($data['id']),
                $result->fetchAllAssociative()
            );
        } else {
            // MySQL version
            return $qb
                ->where("JSON_CONTAINS(a.tags, :tag_json) = 1")
                ->setParameter('tag_json', json_encode($tag))
                ->orderBy('a.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }
    }
}
