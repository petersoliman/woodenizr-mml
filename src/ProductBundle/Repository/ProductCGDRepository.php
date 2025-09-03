<?php

namespace App\ProductBundle\Repository;

use App\ProductBundle\Entity\ProductCGD;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for ProductCGD entity - Temporary storage for Category Generate Data products
 * Created by cursor on 2025-01-27 15:46:00 to handle database operations for CGData approval workflow
 * 
 * @extends ServiceEntityRepository<ProductCGD>
 *
 * @method ProductCGD|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCGD|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCGD[]    findAll()
 * @method ProductCGD[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCGDRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCGD::class);
    }

    /**
     * Find all pending ProductCGD entries
     */
    public function findPending(): array
    {
        return $this->findBy(['status' => 'pending'], ['created' => 'DESC']);
    }

    /**
     * Find all approved ProductCGD entries
     */
    public function findApproved(): array
    {
        return $this->findBy(['status' => 'approved'], ['approvedAt' => 'DESC']);
    }

    /**
     * Find all rejected ProductCGD entries
     */
    public function findRejected(): array
    {
        return $this->findBy(['status' => 'rejected'], ['updatedAt' => 'DESC']);
    }

    /**
     * Find ProductCGD entries by batch ID
     */
    public function findByBatchId(string $batchId): array
    {
        return $this->findBy(['batchId' => $batchId], ['created' => 'ASC']);
    }

    /**
     * Find ProductCGD entries by category
     */
    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('pcgd')
            ->andWhere('pcgd.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('pcgd.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ProductCGD entries by brand
     */
    public function findByBrand(int $brandId): array
    {
        return $this->createQueryBuilder('pcgd')
            ->andWhere('pcgd.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->orderBy('pcgd.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count ProductCGD entries by status
     */
    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * Count pending ProductCGD entries
     */
    public function countPending(): int
    {
        return $this->countByStatus('pending');
    }

    /**
     * Count approved ProductCGD entries
     */
    public function countApproved(): int
    {
        return $this->countByStatus('approved');
    }

    /**
     * Count rejected ProductCGD entries
     */
    public function countRejected(): int
    {
        return $this->countByStatus('rejected');
    }

    /**
     * Find ProductCGD entries for admin approval with pagination
     */
    public function findForApproval(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('productCGD')
            ->orderBy('productCGD.created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ProductCGD entries by status with pagination
     */
    public function findByStatusWithPagination(string $status, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('productCGD')
            ->andWhere('productCGD.status = :status')
            ->setParameter('status', $status)
            ->orderBy('productCGD.created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search ProductCGD entries by name, SKU, or description
     */
    public function search(string $query, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('productCGD')
            ->andWhere('productCGD.name LIKE :query OR productCGD.sku LIKE :query OR productCGD.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('productCGD.created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
