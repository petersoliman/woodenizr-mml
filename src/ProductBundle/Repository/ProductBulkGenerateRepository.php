<?php

namespace App\ProductBundle\Repository;

use App\ProductBundle\Entity\ProductBulkGenerate;
use App\ProductBundle\Enum\ProductBulkGenerateTypeEnum;
use App\UserBundle\Entity\User;
use App\ContentBundle\Entity\Post;
use App\ContentBundle\Entity\Translation\PostTranslation;
use PN\LocaleBundle\Entity\Language;
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
                SUM(pbg.errorCount) as totalErrors
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
            'avgDurationSeconds' => 0,
            'avgDurationMinutes' => 0,
        ];
    }

    public function generateAllProductsCore(int $brandId): int
    {
        // Get the brand to extract the brand name
        $brand = $this->getEntityManager()
            ->getRepository('App\ProductBundle\Entity\Brand')
            ->find($brandId);
        
        if (!$brand) {
            throw new \InvalidArgumentException('Brand not found with ID: ' . $brandId);
        }
        
        $brandName = $brand->getTitle();
        
        // Find all products with the given brand ID
        $products = $this->getEntityManager()
            ->getRepository('App\ProductBundle\Entity\Product')
            ->createQueryBuilder('p')
            ->where('p.brand = :brandId')
            ->andWhere('p.deleted IS NULL')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getResult();
        
        $processedCount = 0;
        
        foreach ($products as $product) {
            try {
                // Get the model number (SKU) from the product
                $modelNumber = $product->getSku();
                
                if (!$modelNumber) {
                    continue; // Skip products without SKU
                }
                
                // Make cURL request to get product descriptions
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://seo-catalog-generator.com/api.php?model_number=" . urlencode($product->getModelNumber()),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "Accept: application/json"
                    ],
                ]);
                
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                
                if ($err) {
                    // Log error and continue with next product
                    error_log("cURL Error for product {$product->getId()}: " . $err);
                    continue;
                }
                
                $data = json_decode($response, true);
                
                if ($data && isset($data['desc_en']) && isset($data['desc_ar'])) {
                    // Get or create the product's post entity for storing descriptions
                    $post = $product->getPost();
                    if (!$post) {
                        $post = new Post();
                        $product->setPost($post);
                    }
                    
                    // Get or create translations for English and Arabic
                    $enTranslation = null;
                    $arTranslation = null;
                    
                    foreach ($post->getTranslations() as $translation) {
                        if ($translation->getLanguage()->getCode() === 'en') {
                            $enTranslation = $translation;
                        } elseif ($translation->getLanguage()->getCode() === 'ar') {
                            $arTranslation = $translation;
                        }
                    }
                    
                    // Create English translation if it doesn't exist
                    if (!$enTranslation) {
                        $enLanguage = $this->getEntityManager()
                            ->getRepository(Language::class)
                            ->findOneBy(['code' => 'en']);
                        
                        if ($enLanguage) {
                            $enTranslation = new PostTranslation();
                            $enTranslation->setLanguage($enLanguage);
                            $enTranslation->setTranslatable($post);
                            $post->addTranslation($enTranslation);
                        }
                    }
                    
                    // Create Arabic translation if it doesn't exist
                    if (!$arTranslation) {
                        $arLanguage = $this->getEntityManager()
                            ->getRepository(Language::class)
                            ->findOneBy(['code' => 'ar']);
                        
                        if ($arLanguage) {
                            $arTranslation = new PostTranslation();
                            $arTranslation->setLanguage($arLanguage);
                            $arTranslation->setTranslatable($post);
                            $post->addTranslation($arTranslation);
                        }
                    }
                    
                    // Set the descriptions
                    if ($enTranslation) {
                        $enTranslation->setContent($data['desc_en']);
                    }
                    
                    if ($arTranslation) {
                        $arTranslation->setContent($data['desc_ar']);
                    }
                    
                    // Persist the changes
                    $this->getEntityManager()->persist($post);
                    $this->getEntityManager()->persist($product);
                    
                    $processedCount++;
                }
                
            } catch (\Exception $e) {
                // Log error and continue with next product
                error_log("Error processing product {$product->getId()}: " . $e->getMessage());
                continue;
            }
        }
        
        // Flush all changes
        $this->getEntityManager()->flush();
        
        return $processedCount;
    }



}




