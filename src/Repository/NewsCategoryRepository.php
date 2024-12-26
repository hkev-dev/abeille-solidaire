<?php

namespace App\Repository;

use App\Entity\NewsCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsCategory>
 *
 * @method NewsCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsCategory[]    findAll()
 * @method NewsCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsCategory::class);
    }

    /**
     * @return NewsCategory[] Returns an array of active categories with their article counts
     */
    public function findWithArticleCounts(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(a.id) as articleCount')
            ->leftJoin('c.articles', 'a')
            ->groupBy('c.id')
            ->having('articleCount > 0')
            ->orderBy('articleCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find category by slug with its articles
     */
    public function findOneBySlugWithArticles(string $slug): ?NewsCategory
    {
        return $this->createQueryBuilder('c')
            ->addSelect('a')
            ->leftJoin('c.articles', 'a')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
