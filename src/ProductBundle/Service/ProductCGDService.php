<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\ProductCGD;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Repository\ProductCGDRepository;
use App\ProductBundle\Service\ProductSearchService;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\SeoBundle\Entity\Seo;
use App\ContentBundle\Entity\Post;
use App\ProductBundle\Entity\ProductPrice;
use App\CurrencyBundle\Entity\Currency;
use PN\MediaBundle\Service\UploadImageService;

/**
 * ProductCGD Service - Handles operations for Category Generate Data approval workflow
 * Created by cursor on 2025-01-27 15:47:00 to manage temporary product data and approval process
 */
class ProductCGDService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductCGDRepository $productCGDRepository,
        private ProductSearchService $productSearchService,
        private UploadImageService $uploadImageService
    ) {}

    /**
     * Convert ALL approved ProductCGD entries to actual Product entities.
     * Created by cursor on 2025-09-01 00:00:00 to move approved temp products into products table and re-index search.
     * Updated by cursor on 2025-09-01 00:35:00 to set status to 'converted' after successful creation
     */
    public function convertAllApprovedToProducts(): array
    {
        $approvedEntries = $this->productCGDRepository->findApproved();
        $results = [];
        $convertedCount = 0;
        $errorCount = 0;

        foreach ($approvedEntries as $productCGD) {
            try {
                // Convert to Product
                $product = $this->createProductFromCGDProduct($productCGD);

                // Mark CGD as processing and link to product id after persist
                $productCGD->setStatus('processing');

                $this->entityManager->persist($product);
                $this->entityManager->flush();

                $productCGD->setProductId($product->getId());
                $productCGD->setStatus('converted');
                $this->entityManager->persist($productCGD);

                // Re-index search table for this product per project convention
                $this->productSearchService->insertOrDeleteProductInSearch($product);

                $convertedCount++;
                $results[] = [
                    'success' => true,
                    'action' => 'converted',
                    'product_cgd_id' => $productCGD->getId(),
                    'product_id' => $product->getId(),
                    'name' => $product->getTitle()
                ];
            } catch (\Exception $e) {
                $errorCount++;
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'id' => $productCGD instanceof ProductCGD ? $productCGD->getId() : null
                ];
            }
        }

        // Flush remaining changes
        $this->entityManager->flush();

        return [
            'total' => count($approvedEntries),
            'converted' => $convertedCount,
            'errors' => $errorCount,
            'results' => $results
        ];
    }



    /**
     * Convert a single ProductCGD to Product entity
     * Updated by cursor on 2025-09-01 00:15:00 to attach SEO, Post, Currency, and ProductPrice
     */
    /**
     * Renamed by cursor on 2025-09-01 00:22:00 from convertProductCGDToProduct to createProductFromCGDProduct
     */
    private function createProductFromCGDProduct(ProductCGD $productCGD): Product
    {
        $product = new Product();
        $product->setTitle($productCGD->getName());
        $product->setSku($productCGD->getSku());
        $product->setCategory($productCGD->getCategory());
        // Set brand if available
        if ($productCGD->getBrand()) {
            $product->setBrand($productCGD->getBrand());
        }

        // Set publish to false by default for review
        $product->setPublish(false);

        // Set default currency (EGP) if exists
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'EGP']);
        if ($currency instanceof Currency) {
            $product->setCurrency($currency);
        }

        // Create and attach SEO
        $seo = $this->createSeoForProduct($product, $productCGD->getDescription());
        $product->setSeo($seo);

        // Create and attach Post
        $post = $this->createPostForProduct($product, $productCGD->getDescription(), $productCGD->getTechnicalSpecs() ?? []);
        $product->setPost($post);

        // Create minimal ProductPrice
        $this->createProductPriceForProduct($product, $productCGD->getPrice());

        // Attach images from CGD external images list
        $this->attachImagesFromCGD($product, $productCGD);

        return $product;
    }

    /**
     * Attach images from CGD to Product Post by downloading URLs via UploadImageService
     * Created by cursor on 2025-09-01 01:40:00 to persist product images during conversion
     */
    private function attachImagesFromCGD(Product $product, ProductCGD $productCGD): void
    {
        $images = $productCGD->getImages();
        if (!is_array($images) || empty($images)) {
            // Try external data field
            $external = $productCGD->getExternalData();
            if (is_array($external) && isset($external['images']) && is_array($external['images'])) {
                $images = $external['images'];
            }
        }

        if (!is_array($images) || empty($images)) {
            return;
        }

        $post = $product->getPost();
        if (!$post) {
            return;
        }

        $imageSettingId = 1; // Matches product post images in existing controllers

        foreach ($images as $index => $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }
            try {
                // First image as main, others as gallery
                $imageType = \PN\MediaBundle\Entity\Image::TYPE_MAIN;
                if ($index > 0) {
                    $imageType = \PN\MediaBundle\Entity\Image::TYPE_GALLERY;
                }
                $this->uploadImageService->uploadSingleImageByUrl($post, $url, $imageSettingId, null, $imageType);
            } catch (\Throwable $e) {
                // Non-fatal: skip bad image URLs
                continue;
            }
        }
    }



    /**
     * Save CGData products to temporary ProductCGD table for admin approval
     * Updated by cursor on 2025-09-01 00:00:00 to remove debug exit and stabilize save flow
     */
    /**
     * Renamed by cursor on 2025-09-01 00:20:00 from saveCGDataToTemporary to saveCGDListToTemp
     * Updated by cursor on 2025-09-01 00:28:00 to validate input items and skip duplicates
     * Updated by cursor on 2025-09-01 00:58:00 to enforce uniqueness in temp by (sku, brand_id)
     */
    public function saveCGDListToTemp(array $products, Category $category, Brand $brand, User $createdBy, ?string $batchId = null): array
    {

        $results = [];
        $savedCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($products as $productData) {
            try {
                // Validate product data before saving to temp
                $validation = $this->validateProductData($productData);

                if ($validation['isValid'] === false) {
                    if (!empty($validation['isDuplicate'])) {
                        // Skip duplicates that already exist in main products table
                        $skippedCount++;
                        $results[] = [
                            'success' => true,
                            'action' => 'skipped_duplicate',
                            'sku' => $validation['sku'] ?? ($productData['sku'] ?? null),
                            'name' => $validation['name'] ?? ($productData['name'] ?? null),
                            'existing_product_id' => $validation['existing_product_id'] ?? null,
                        ];
                        continue;
                    }

                    // Validation errors - don't save
                    $errorCount++;
                    $results[] = [
                        'success' => false,
                        'action' => 'validation_failed',
                        'errors' => $validation['errors'] ?? ['Invalid product data'],
                        'sku' => $productData['sku'] ?? null,
                        'name' => $productData['name'] ?? null,
                    ];
                    continue;
                }

                // Enforce uniqueness in temp table by (sku, brand_id)
                $skuToCheck = $productData['sku'] ?? null;
                if ($skuToCheck) {
                    $existingTemp = $this->productCGDRepository->findOneBy([
                        'sku' => $skuToCheck,
                        'brand' => $brand,
                    ]);
                    if ($existingTemp) {
                        $skippedCount++;
                        $results[] = [
                            'success' => true,
                            'action' => 'skipped_temp_duplicate',
                            'sku' => $skuToCheck,
                            'name' => $productData['name'] ?? null,
                            'existing_cgd_id' => $existingTemp->getId(),
                            'existing_cgd_status' => $existingTemp->getStatus(),
                        ];
                        continue;
                    }
                }

                $productCGD = $this->createCGDProdObject($productData, $category, $brand, $createdBy, $batchId);
                
                $this->entityManager->persist($productCGD);
                $savedCount++;
                
                $results[] = [
                    'success' => true,
                    'action' => 'saved_to_temp',
                    'product_cgd_id' => $productCGD->getId(),
                    'name' => $productCGD->getName(),
                    'sku' => $productCGD->getSku()
                ];
                
            } catch (\Exception $e) {
                $errorCount++;
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'product_data' => $productData
                ];
            }
        }

        // Flush all changes at once
        $this->entityManager->flush();

        return [
            'total' => count($products),
            'saved' => $savedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
            'results' => $results
        ];
    }


    /**
     * Create a ProductCGD entity from product data
     */
    /**
     * Renamed by cursor on 2025-09-01 00:20:00 from createProductCGDFromData to createCGDProdObject
     */
    private function createCGDProdObject(array $productData, Category $category, Brand $brand, User $createdBy, ?string $batchId): ProductCGD
    {

        $productCGD = new ProductCGD();
        
        $productCGD->setCategory($category);
        $productCGD->setBrand($brand);
        $productCGD->setCreatedBy($createdBy);
        $productCGD->setBatchId($batchId);
        
        // Set basic product information
        $productCGD->setName($productData['name'] ?? '');
        $productCGD->setDescription($productData['description'] ?? null);
        $productCGD->setSku($productData['sku'] ?? null);
        $productCGD->setUrl($productData['url'] ?? null);
        
        // Handle price
        if (isset($productData['price']) && is_numeric($productData['price'])) {
            $productCGD->setPrice((string) $productData['price']);
        }
        
        // Handle images (store as JSON array of URLs)
        if (isset($productData['images']) && is_array($productData['images'])) {
            $productCGD->setImages($productData['images']);
        }
        
        // Handle technical specs
        if (isset($productData['technical_specs']) && is_array($productData['technical_specs'])) {
            $productCGD->setTechnicalSpecs($productData['technical_specs']);
        }
        
        // Store additional metadata
        $metadata = [];
        if (isset($productData['brand_name'])) {
            $metadata['brand_name'] = $productData['brand_name'];
        }
        if (isset($productData['assigned_category_id'])) {
            $metadata['assigned_category_id'] = $productData['assigned_category_id'];
        }
        if (isset($productData['assigned_brand_id'])) {
            $metadata['assigned_brand_id'] = $productData['assigned_brand_id'];
        }
        
        if (!empty($metadata)) {
            $productCGD->setMetadata($metadata);
        }
        
        // Store original external data for reference
        $productCGD->setExternalData($productData);
        
        return $productCGD;
    }

    /**
     * Reject a ProductCGD entry
     */
    public function rejectProductCGD(ProductCGD $productCGD, User $rejectedBy, string $reason): array
    {
        try {
            $productCGD->setStatus('rejected');
            $productCGD->setRejectionReason($reason);
            
            $this->entityManager->persist($productCGD);
            $this->entityManager->flush();
            
            return [
                'success' => true,
                'message' => 'ProductCGD rejected successfully',
                'product_cgd_id' => $productCGD->getId()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to reject ProductCGD: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve a ProductCGD entry and mark approved meta
     * Restored by cursor on 2025-09-01 02:05:00 after controller references
     */
    public function approveProductCGD(ProductCGD $productCGD, User $approvedBy): array
    {
        try {
            $productCGD->setStatus('approved');
            $productCGD->setApprovedBy($approvedBy);
            $productCGD->setApprovedAt(new \DateTime());

            $this->entityManager->persist($productCGD);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'ProductCGD approved successfully',
                'product_cgd_id' => $productCGD->getId()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to approve ProductCGD: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate Product Data from array - for CGData validation before entity creation
     * Created by cursor on 2025-09-01 00:15:00 moved from controller to service for reuse
     */
    public function validateProductData(array $productData): array
    {
        $errors = [];

        $name = $productData['name'] ?? null;
        if (!$name || trim($name) === '') {
            $errors[] = 'Product name is required';
        }

        $sku = $productData['sku'] ?? null;
        if (!$sku || trim($sku) === '') {
            $errors[] = 'Product SKU is required';
        }

        if ($sku) {
            $existingProduct = $this->entityManager->getRepository(Product::class)->findOneBy([
                'sku' => $sku,
                'deleted' => null
            ]);

            if ($existingProduct) {
                return [
                    'isValid' => false,
                    'isDuplicate' => true,
                    'action' => 'skipped',
                    'message' => 'Product already exists in database',
                    'sku' => $sku,
                    'name' => $name,
                    'existing_product_id' => $existingProduct->getId(),
                    'existing_brand_name' => $existingProduct->getBrand() ? $existingProduct->getBrand()->getTitle() : null,
                    'existing_brand_id' => $existingProduct->getBrand() ? $existingProduct->getBrand()->getId() : null,
                    'errors' => ['Product with SKU already exists']
                ];
            }
        }

        return [
            'isValid' => empty($errors),
            'isDuplicate' => false,
            'errors' => $errors
        ];
    }

    /**
     * Generate a unique slug for SEO
     * Created by cursor on 2025-09-01 00:15:00 moved from controller to service for reuse
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $originalSlug = $slug;
        $counter = 1;

        while ($this->entityManager->getRepository(Seo::class)->findOneBy(['slug' => $slug, 'deleted' => null])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Create and save SEO entity for product
     * Created by cursor on 2025-09-01 00:15:00 moved from controller to service for reuse
     */
    private function createSeoForProduct(Product $product, ?string $description): Seo
    {
        $name = $product->getTitle();

        $seo = new Seo();
        $seo->setTitle($name);
        $seo->setMetaDescription(null);
        $slug = $this->generateUniqueSlug($name);
        $seo->setSlug($slug);

        $this->entityManager->persist($seo);
        return $seo;
    }

    /**
     * Create and save Post entity for product content
     * Created by cursor on 2025-09-01 00:15:00 moved from controller to service for reuse
     */
    private function createPostForProduct(Product $product, ?string $description, array $technicalSpecs): Post
    {
        $post = new Post();

        if ($description) {
            $post->setShortDescription(substr($description, 0, 255));
            $post->setDescription($description);
        }

        $this->entityManager->persist($post);
        return $post;
    }

    /**
     * Create and save ProductPrice entity for product
     * Created by cursor on 2025-09-01 00:15:00 moved from controller to service for reuse
     */
    private function createProductPriceForProduct(Product $product, ?string $price): ProductPrice
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($product);

        $priceValue = $price && is_numeric($price) ? (float)$price : 0.00;
        $productPrice->setUnitPrice($priceValue);
        $productPrice->setPromotionalPrice($priceValue > 0 ? $priceValue : 0.01);

        $this->entityManager->persist($productPrice);
        return $productPrice;
    }

    /**
     * Get statistics for ProductCGD entries
     */
    public function getStatistics(): array
    {
        return [
            'pending' => $this->productCGDRepository->countPending(),
            'approved' => $this->productCGDRepository->countApproved(),
            'rejected' => $this->productCGDRepository->countRejected(),
            'converted' => $this->productCGDRepository->countByStatus('converted'),
            'processing' => $this->productCGDRepository->countByStatus('processing'),
            'total' => $this->productCGDRepository->count([])
        ];
    }

    /**
     * Physically delete rejected ProductCGD entries
     * Created by cursor on 2025-09-01 00:45:00 per requirement
     */
    public function deleteRejected(array $ids = null): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        if ($ids && count($ids) > 0) {
            $qb->delete(ProductCGD::class, 'p')
                ->where('p.status = :status')
                ->andWhere($qb->expr()->in('p.id', ':ids'))
                ->setParameter('status', 'rejected')
                ->setParameter('ids', $ids);
        } else {
            $qb->delete(ProductCGD::class, 'p')
                ->where('p.status = :status')
                ->setParameter('status', 'rejected');
        }

        return (int) $qb->getQuery()->execute();
    }

    /**
     * Get ProductCGD entries for admin approval with pagination
     */
    public function getForApproval(int $page = 1, int $limit = 20): array
    {
        return $this->productCGDRepository->findForApproval($page, $limit);
    }

    /**
     * Get ProductCGD entries by status with pagination
     */
    public function getByStatus(string $status, int $page = 1, int $limit = 20): array
    {
        return $this->productCGDRepository->findByStatusWithPagination($status, $page, $limit);
    }

    /**
     * Search ProductCGD entries
     */
    public function search(string $query, int $page = 1, int $limit = 20): array
    {
        return $this->productCGDRepository->search($query, $page, $limit);
    }
}
