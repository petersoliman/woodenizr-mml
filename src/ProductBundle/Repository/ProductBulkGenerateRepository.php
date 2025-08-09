<?php

namespace App\ProductBundle\Repository;

use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductBulkGenerate>
 *
 * @method ProductBulkGenerate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductBulkGenerate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductBulkGenerate[]    findAll()
 * @method ProductBulkGenerate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductBulkGenerateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductBulkGenerate::class);
    }

    /**
     * Filter bulk generate jobs based on search criteria
     */
    public function filter($search, $countOnly = false, $limitStart = null, $pageLimit = null): array|int
    {
        $qb = $this->createQueryBuilder('pbg')
            ->leftJoin('pbg.admin', 'a')
            ->where('pbg.deleted IS NULL');

        $this->addSearchCriteria($qb, $search);

        if ($countOnly) {
            return (int) $qb->select('COUNT(pbg.id)')->getQuery()->getSingleScalarResult();
        }

        $qb->orderBy('pbg.created', 'DESC');

        if ($limitStart !== null && $pageLimit !== null) {
            $qb->setFirstResult($limitStart)->setMaxResults($pageLimit);
        }

        return $qb->getQuery()->getResult();
    }

    private function addSearchCriteria(QueryBuilder $qb, $search): void
    {
        if (isset($search->generatedFor) && $search->generatedFor !== '') {
            $qb->andWhere('pbg.generatedFor = :generatedFor')
                ->setParameter('generatedFor', $search->generatedFor);
        }

        if (isset($search->status) && !empty($search->status)) {
            $qb->andWhere('pbg.status = :status')
                ->setParameter('status', $search->status);
        }

        if (isset($search->adminId) && !empty($search->adminId)) {
            $qb->andWhere('pbg.admin = :adminId')
                ->setParameter('adminId', $search->adminId);
        }

        if (isset($search->createdFrom) && !empty($search->createdFrom)) {
            $qb->andWhere('DATE(pbg.created) >= :createdFrom')
                ->setParameter('createdFrom', $search->createdFrom);
        }

        if (isset($search->createdTo) && !empty($search->createdTo)) {
            $qb->andWhere('DATE(pbg.created) <= :createdTo')
                ->setParameter('createdTo', $search->createdTo);
        }

        if (isset($search->startDateFrom) && !empty($search->startDateFrom)) {
            $qb->andWhere('DATE(pbg.startDate) >= :startDateFrom')
                ->setParameter('startDateFrom', $search->startDateFrom);
        }

        if (isset($search->startDateTo) && !empty($search->startDateTo)) {
            $qb->andWhere('DATE(pbg.startDate) <= :startDateTo')
                ->setParameter('startDateTo', $search->startDateTo);
        }

        if (isset($search->ids) && is_array($search->ids) && count($search->ids) > 0) {
            $qb->andWhere('pbg.id IN (:ids)')
                ->setParameter('ids', $search->ids);
        }
    }

    /**
     * Find bulk generate jobs by type
     */
    public function findByType(ProductBulkGenerateTypeEnum $type): array
    {
        return $this->createQueryBuilder('pbg')
            ->where('pbg.generatedFor = :type')
            ->andWhere('pbg.deleted IS NULL')
            ->setParameter('type', $type->value)
            ->orderBy('pbg.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find bulk generate jobs by admin
     */
    public function findByAdmin(User $admin): array
    {
        return $this->createQueryBuilder('pbg')
            ->where('pbg.admin = :admin')
            ->andWhere('pbg.deleted IS NULL')
            ->setParameter('admin', $admin)
            ->orderBy('pbg.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find bulk generate jobs by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('pbg')
            ->where('pbg.status = :status')
            ->andWhere('pbg.deleted IS NULL')
            ->setParameter('status', $status)
            ->orderBy('pbg.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count jobs by status
     */
    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('pbg')
            ->select('COUNT(pbg.id)')
            ->where('pbg.status = :status')
            ->andWhere('pbg.deleted IS NULL')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count jobs by type
     */
    public function countByType(ProductBulkGenerateTypeEnum $type): int
    {
        return (int) $this->createQueryBuilder('pbg')
            ->select('COUNT(pbg.id)')
            ->where('pbg.generatedFor = :type')
            ->andWhere('pbg.deleted IS NULL')
            ->setParameter('type', $type->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        // Statistics by status
        $statusQb = $this->createQueryBuilder('pbg')
            ->select('pbg.status, COUNT(pbg.id) as count')
            ->where('pbg.deleted IS NULL')
            ->groupBy('pbg.status');

        $statusResults = $statusQb->getQuery()->getResult();

        // Statistics by type
        $typeQb = $this->createQueryBuilder('pbg')
            ->select('pbg.generatedFor, COUNT(pbg.id) as count')
            ->where('pbg.deleted IS NULL')
            ->groupBy('pbg.generatedFor');

        $typeResults = $typeQb->getQuery()->getResult();

        // Total recommendations generated
        $totalRecommendationsQb = $this->createQueryBuilder('pbg')
            ->select('SUM(pbg.totalRecommendations) as total')
            ->where('pbg.deleted IS NULL');

        $totalRecommendations = $totalRecommendationsQb->getQuery()->getSingleScalarResult() ?? 0;

        $stats = [
            'total' => 0,
            'totalRecommendations' => (int) $totalRecommendations,
            'byStatus' => [
                'pending' => 0,
                'running' => 0,
                'completed' => 0,
                'failed' => 0,
            ],
            'byType' => [
                ProductBulkGenerateTypeEnum::SEO->value => 0,
                ProductBulkGenerateTypeEnum::PRICES->value => 0,
                ProductBulkGenerateTypeEnum::GENERAL->value => 0,
            ]
        ];

        foreach ($statusResults as $result) {
            $stats['byStatus'][$result['status']] = (int) $result['count'];
            $stats['total'] += (int) $result['count'];
        }

        foreach ($typeResults as $result) {
            $stats['byType'][$result['generatedFor']] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Find latest jobs
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('pbg')
            ->leftJoin('pbg.admin', 'a')
            ->where('pbg.deleted IS NULL')
            ->orderBy('pbg.created', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find running jobs
     */
    public function findRunningJobs(): array
    {
        return $this->findByStatus('running');
    }

    /**
     * Find jobs that need to be processed
     */
    public function findPendingJobs(): array
    {
        return $this->findByStatus('pending');
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        $qb = $this->createQueryBuilder('pbg')
            ->select('
                COUNT(pbg.id) as totalJobs,
                AVG(pbg.totalRecommendations) as avgRecommendations,
                SUM(pbg.processedCount) as totalProcessed,
                SUM(pbg.errorCount) as totalErrors,
                AVG(TIMESTAMPDIFF(SECOND, pbg.startDate, pbg.endDate)) as avgDurationSeconds
            ')
            ->where('pbg.deleted IS NULL')
            ->andWhere('pbg.status = :status')
            ->setParameter('status', 'completed');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalJobs' => (int) ($result['totalJobs'] ?? 0),
            'avgRecommendations' => round((float) ($result['avgRecommendations'] ?? 0), 2),
            'totalProcessed' => (int) ($result['totalProcessed'] ?? 0),
            'totalErrors' => (int) ($result['totalErrors'] ?? 0),
            'avgDurationSeconds' => (int) ($result['avgDurationSeconds'] ?? 0),
            'avgDurationMinutes' => round(((int) ($result['avgDurationSeconds'] ?? 0)) / 60, 2),
        ];
    }
}




