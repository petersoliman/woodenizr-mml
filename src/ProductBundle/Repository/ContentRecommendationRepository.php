<?php

namespace App\ProductBundle\Repository;

use App\ProductBundle\Entity\ContentRecommendation;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Enum\ContentRecommendationStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentRecommendation>
 *
 * @method ContentRecommendation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentRecommendation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentRecommendation[]    findAll()
 * @method ContentRecommendation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentRecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentRecommendation::class);
    }

    /**
     * Filter content recommendations based on search criteria
     */
    public function filter($search, $countOnly = false, $limitStart = null, $pageLimit = null): array|int
    {
        $qb = $this->createQueryBuilder('cr')
            ->leftJoin('cr.product', 'p')
            ->where('cr.deleted IS NULL');

        $this->addSearchCriteria($qb, $search);

        if ($countOnly) {
            return (int) $qb->select('COUNT(cr.id)')->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy('cr.created', 'DESC');

        if ($limitStart !== null && $pageLimit !== null) {
            $qb->setFirstResult($limitStart)->setMaxResults($pageLimit);
        }

        return $qb->getQuery()->getResult();
    }

    private function addSearchCriteria(QueryBuilder $qb, $search): void
    {
        if (isset($search->productId) && !empty($search->productId)) {
            $qb->andWhere('cr.product = :productId')
                ->setParameter('productId', $search->productId);
        }

        if (isset($search->state) && $search->state !== '') {
            $qb->andWhere('cr.state = :state')
                ->setParameter('state', $search->state);
        }

        if (isset($search->productTitle) && !empty($search->productTitle)) {
            $qb->andWhere('p.title LIKE :productTitle')
                ->setParameter('productTitle', '%' . $search->productTitle . '%');
        }

        if (isset($search->createdFrom) && !empty($search->createdFrom)) {
            $qb->andWhere('DATE(cr.created) >= :createdFrom')
                ->setParameter('createdFrom', $search->createdFrom);
        }

        if (isset($search->createdTo) && !empty($search->createdTo)) {
            $qb->andWhere('DATE(cr.created) <= :createdTo')
                ->setParameter('createdTo', $search->createdTo);
        }

        if (isset($search->ids) && is_array($search->ids) && count($search->ids) > 0) {
            $qb->andWhere('cr.id IN (:ids)')
                ->setParameter('ids', $search->ids);
        }
    }

    /**
     * Find content recommendations by product
     */
    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.product = :product')
            ->andWhere('cr.deleted IS NULL')
            ->setParameter('product', $product)
            ->orderBy('cr.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find content recommendations by state
     */
    public function findByState(ContentRecommendationStateEnum $state): array
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.state = :state')
            ->andWhere('cr.deleted IS NULL')
            ->setParameter('state', $state->value)
            ->orderBy('cr.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count recommendations by state
     */
    public function countByState(ContentRecommendationStateEnum $state): int
    {
        return (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where('cr.state = :state')
            ->andWhere('cr.deleted IS NULL')
            ->setParameter('state', $state->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->select('cr.state, COUNT(cr.id) as count')
            ->where('cr.deleted IS NULL')
            ->groupBy('cr.state');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'total' => 0,
            ContentRecommendationStateEnum::NEW->value => 0,
            ContentRecommendationStateEnum::ACCEPTED->value => 0,
            ContentRecommendationStateEnum::REJECTED->value => 0,
        ];

        foreach ($results as $result) {
            $stats[$result['state']] = (int) $result['count'];
            $stats['total'] += (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Find latest recommendations
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('cr')
            ->leftJoin('cr.product', 'p')
            ->where('cr.deleted IS NULL')
            ->orderBy('cr.created', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}




