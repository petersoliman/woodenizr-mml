<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductCGD;
use App\ProductBundle\Entity\ProductPrice;
use App\CurrencyBundle\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use PN\MediaBundle\Entity\Image as MediaImage;

/**
 * Created by: cursor
 * Date: 2025-09-01 00:05
 * Reason: Run GCData updates in batch for products with gcd_status = Ready
 */
class ProductGCDBatchService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly \PN\MediaBundle\Service\UploadImageService $uploadImageService,
        private readonly ?ProductSearchService $productSearchService = null,
    ) {
    }

    /**
     * Fetch manufacturer product URLs by calling external API for products with missing CGD URL.
     * Created by: cursor
     * Date: 2025-09-01 16:10
     * Reason: Populate/refresh ProductCGD.url using sku and brand name
     *
     * @param int|null $limit Optional maximum number of products to attempt
     * @return array{processed:int,updated:int,skipped:int,errors:int}
     */
    public function fetchAndSaveManufacturerUrls(?int $limit = null): array
    {
        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        // Fetch products with non-empty SKU, oldest first
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(\App\ProductBundle\Entity\Product::class, 'p')
            ->where('p.sku IS NOT NULL AND p.sku <> :empty')
            ->setParameter('empty', '')
            ->orderBy('p.id', 'ASC');
        if ($limit !== null) {
            $qb->setMaxResults(max(1, (int)$limit));
        }
        $products = $qb->getQuery()->toIterable();

        foreach ($products as $product) {
            if (!$product instanceof \App\ProductBundle\Entity\Product) { continue; }
            $processed++;
            try {
                // Skip if CGD exists and already has a URL
                $existing = $this->em->getRepository(\App\ProductBundle\Entity\ProductCGD::class)
                    ->findOneBy(['productId' => $product->getId()], ['created' => 'DESC']);
                if ($existing && $existing->getUrl()) { $skipped++; continue; }

                $sku = (string) $product->getSku();
                $brandTitle = $product->getBrand() ? (string) $product->getBrand()->getTitle() : 'Not specified';

                $apiUrl = 'http://localhost:8013/product_url_api.php';
                $postData = http_build_query(['sku' => $sku, 'brand' => $brandTitle]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) { $errors++; continue; }
                $data = json_decode($response ?? '', true);
                if (!is_array($data)) { $errors++; continue; }
                $url = $data['url'] ?? null;
                if (!is_string($url) || trim($url) === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $skipped++;
                    continue;
                }

                // Ensure ProductCGD exists
                $cgd = $existing;
                if (!$cgd) {
                    $cgd = new \App\ProductBundle\Entity\ProductCGD();
                    // Required fields: category, name; optional: brand
                    if ($product->getCategory()) { $cgd->setCategory($product->getCategory()); }
                    if ($product->getBrand()) { $cgd->setBrand($product->getBrand()); }
                    $cgd->setName((string) $product->getTitle());
                    $cgd->setSku($sku);
                    $cgd->setProductId($product->getId());
                    $cgd->setStatus('pending');
                }
                $cgd->setUrl($url);
                $this->em->persist($cgd);
                $this->em->flush();
                $updated++;
            } catch (\Throwable $e) {
                $errors++;
                // continue with next product
            }

            // Light throttle
            usleep(100000);
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Execute GCData update for all products with gcd_status = Ready
     * Updated: 2025-09-01 00:05
     */
    public function runAllReady(string $jobId, ?int $limit = null): void
    {
        $repo = $this->em->getRepository(Product::class);
        $qb = $repo->createQueryBuilder('p')
            ->andWhere('p.gcdStatus = :st')
            ->setParameter('st', 'Ready')
            ->orderBy('p.id', 'ASC');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        $products = $qb->getQuery()->toIterable();

        // Accurate total count
        $total = (int) $this->em->createQuery('SELECT COUNT(p2.id) FROM App\\ProductBundle\\Entity\\Product p2 WHERE p2.gcdStatus = :st')
            ->setParameter('st', 'Ready')
            ->getSingleScalarResult();

        $this->writeProgress($jobId, [
            'status' => 'running',
            'total' => $total,
            'processed' => 0,
            'errors' => 0,
            'currentProductId' => null,
            'startedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $processed = 0;
        $errors = 0;
        $items = [];
        $terminated = false;
        foreach ($products as $product) {
            if (!$product instanceof Product) { continue; }

            // Check for terminate signal
            $control = $this->readControl($jobId);
            if (isset($control['terminate']) && $control['terminate']) {
                $terminated = true;
                break;
            }

            $this->writeProgress($jobId, [
                'status' => 'running',
                'total' => $total,
                'processed' => $processed,
                'errors' => $errors,
                'currentProductId' => $product->getId(),
            ]);

            try {
                // Try to link CGD and mark as processing during batch
                try {
                    $linkedCGD = $this->em->getRepository(ProductCGD::class)
                        ->findOneBy(['productId' => $product->getId()], ['created' => 'DESC']);
                    if (!$linkedCGD && $product->getSku()) {
                        $linkedCGD = $this->em->getRepository(ProductCGD::class)
                            ->findOneBy(['sku' => $product->getSku()], ['created' => 'DESC']);
                    }
                    if ($linkedCGD) {
                        $linkedCGD->setStatus('processing');
                        $this->em->persist($linkedCGD);
                    }
                } catch (\Throwable $e) { /* ignore */ }

                $product->setGcdStatus('Generating');
                $this->em->persist($product);
                $this->em->flush();

                $api = $this->fetchApiDataForProduct($product);
                if ($api && ($api['success'] ?? false)) {
                    $flags = $this->applyApiDataToProduct($product, $api);
                    $product->setGcdStatus('Done');
                    // mark CGD converted
                    if (isset($linkedCGD) && $linkedCGD) {
                        try { $linkedCGD->setStatus('converted'); $this->em->persist($linkedCGD); } catch (\Throwable $e) { /* ignore */ }
                    }
                    $items[] = [
                        'productId' => $product->getId(),
                        'sku' => $product->getSku(),
                        'title' => $product->getTitle(),
                        'result' => 'updated',
                        'flags' => $flags,
                    ];
                } else {
                    $product->setGcdStatus('Done'); // Mark as done to avoid reprocessing; could use Failed state if needed
                    $items[] = [
                        'productId' => $product->getId(),
                        'sku' => $product->getSku(),
                        'title' => $product->getTitle(),
                        'result' => 'skipped',
                        'error' => $api['error'] ?? 'unknown',
                    ];
                }

                $this->em->persist($product);
                $this->em->flush();

                if ($this->productSearchService) {
                    try { $this->productSearchService->saveProductInProductSearch($product); } catch (\Throwable $e) { /* ignore */ }
                }

                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                $items[] = [
                    'productId' => $product->getId(),
                    'sku' => $product->getSku(),
                    'title' => $product->getTitle(),
                    'result' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }

            $this->writeProgress($jobId, [
                'status' => 'running',
                'total' => $total,
                'processed' => $processed,
                'errors' => $errors,
                'currentProductId' => null,
            ]);
            // small delay to avoid hammering external API/UI
            usleep(150000);
        }

        $this->writeProgress($jobId, [
            'status' => $terminated ? 'terminated' : 'completed',
            'total' => $total,
            'processed' => $processed,
            'errors' => $errors,
            'finishedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'items' => $items,
        ]);
    }

    /**
     * Mark all products not Done as Ready to be processed.
     */
    public function markAllNotDoneAsReady(): int
    {
        $qb = $this->em->createQueryBuilder()
            ->update(Product::class, 'p')
            ->set('p.gcdStatus', ':ready')
            ->where('p.gcdStatus IS NULL OR p.gcdStatus <> :done')
            ->setParameter('ready', 'Ready')
            ->setParameter('done', 'Done');
        return (int) $qb->getQuery()->execute();
    }

    /**
     * Fetch API data similarly to ProductController::gcdataFetch
     * Updated: 2025-09-01 00:05
     */
    private function fetchApiDataForProduct(Product $product): ?array
    {
        $sku = $product->getSku();
        $brandName = $product->getBrand() ? $product->getBrand()->getTitle() : 'Not specified';
        if (!$sku) { return ['success' => false, 'error' => 'Empty SKU']; }

        $lookup = $sku;
        try {
            $cgdByProduct = $this->em->getRepository(ProductCGD::class)->findOneBy(['productId' => $product->getId()], ['created' => 'DESC']);
            if ($cgdByProduct && $cgdByProduct->getUrl() && filter_var($cgdByProduct->getUrl(), FILTER_VALIDATE_URL)) {
                $lookup = $cgdByProduct->getUrl();
            } else {
                $cgdBySku = $this->em->getRepository(ProductCGD::class)->findOneBy(['sku' => $sku], ['created' => 'DESC']);
                if ($cgdBySku && $cgdBySku->getUrl() && filter_var($cgdBySku->getUrl(), FILTER_VALIDATE_URL)) {
                    $lookup = $cgdBySku->getUrl();
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $apiUrl = 'http://localhost:8013/single_product_api.php';
        $postData = 'url=' . urlencode($lookup);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) { return ['success' => false, 'error' => $curlError]; }
        $apiData = json_decode($response ?? '', true);
        if (!$apiData) { return ['success' => false, 'error' => 'Invalid JSON']; }
        return $apiData;
    }

    /**
     * Apply API data to product (title, description, images)
     * Updated: 2025-09-01 00:05
     */
    private function applyApiDataToProduct(Product $product, array $api): array
    {
        $productData = $api['product'] ?? [];
        $name = $productData['name'] ?? null;
        $description = $productData['description'] ?? null;
        $images = $productData['images'] ?? [];
        $rawPrice = $productData['price'] ?? null;
        $flags = [
            'titleUpdated' => false,
            'descriptionUpdated' => false,
            'imagesSaved' => 0,
            'priceUpdated' => false,
        ];

        if ($name && $name !== $product->getTitle()) {
            $product->setTitle($name);
            $flags['titleUpdated'] = true;
        }

        if ($description) {
            if (!$product->getPost()) {
                $post = new \App\ContentBundle\Entity\Post();
                $product->setPost($post);
                $this->em->persist($post);
            }
            $content = $product->getPost()->getContent() ?: [];
            $content['description'] = $description;
            $product->getPost()->setContent($content);
            $flags['descriptionUpdated'] = true;
        }

        // Images: take up to first 6
        if (is_array($images) && count($images) > 0 && $product->getPost()) {
            $imageSettingId = 1;
            foreach (array_slice($images, 0, 6) as $index => $url) {
                if (!is_string($url) || trim($url) === '') { continue; }
                $type = $index === 0 ? MediaImage::TYPE_MAIN : MediaImage::TYPE_GALLERY;
                try {
                    $img = $this->uploadImageService->uploadSingleImageByUrl($product->getPost(), $url, $imageSettingId, null, $type);
                    if ($img instanceof MediaImage && $index === 0 && method_exists($product, 'setMainImage')) {
                        $product->setMainImage($img);
                    }
                    $flags['imagesSaved']++;
                } catch (\Throwable $e) {
                    // ignore failed image
                }
            }
        }

        // Price: create or update the product's primary ProductPrice from API price
        $priceValue = $this->parsePriceToFloat($rawPrice);
        if ($priceValue !== null && $priceValue >= 0) {
            $existingPrices = $product->getPrices();
            $priceEntity = $existingPrices->first() ?: null;
            if (!$priceEntity instanceof ProductPrice) {
                $priceEntity = new ProductPrice();
                $priceEntity->setProduct($product);
                // Set currency from product if available, else default to EGP if exists
                $currency = $product->getCurrency();
                if (!$currency instanceof Currency) {
                    $currency = $this->em->getRepository(Currency::class)->findOneBy(['code' => 'EGP']);
                }
                if ($currency instanceof Currency) {
                    $priceEntity->setCurrency($currency);
                }
            }
            $priceEntity->setUnitPrice($priceValue);
            $priceEntity->setPromotionalPrice($priceValue);
            $this->em->persist($priceEntity);
            $flags['priceUpdated'] = true;
        }
        return $flags;
    }

    /**
     * Normalize various price formats to float (e.g., "EGP 1,234.50" -> 1234.50)
     */
    private function parsePriceToFloat($raw): ?float
    {
        if ($raw === null) { return null; }
        if (is_numeric($raw)) { return (float) $raw; }
        if (!is_string($raw)) { return null; }
        $s = trim($raw);
        // Replace non-number separators, keep digits, dots and commas
        // First, unify decimal: if both comma and dot exist, assume comma thousand sep
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace(',', '', $s);
        } else {
            // If only comma exists, treat it as decimal
            if (strpos($s, ',') !== false && strpos($s, '.') === false) {
                $s = str_replace(',', '.', $s);
            }
        }
        // Remove all non-digit and non-dot
        $s = preg_replace('/[^0-9.\-]+/', '', $s) ?? '';
        if ($s === '' || $s === '.' || $s === '-') { return null; }
        if (!is_numeric($s)) { return null; }
        return (float) $s;
    }

    /**
     * Write job progress to var/gcdata_jobs/{jobId}.json
     * Updated: 2025-09-01 00:05
     */
    private function writeProgress(string $jobId, array $data): void
    {
        $base = dirname(__DIR__, 3) . '/var/gcdata_jobs';
        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }
        $file = $base . '/' . basename($jobId) . '.json';
        // Preserve control flags like terminate if present
        $existing = $this->readControl($jobId);
        if (is_array($existing)) {
            foreach (['terminate','batchId','requestedBy','requestedAt'] as $k) {
                if (isset($existing[$k]) && !isset($data[$k])) {
                    $data[$k] = $existing[$k];
                }
            }
        }
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function readControl(string $jobId): ?array
    {
        $file = dirname(__DIR__, 3) . '/var/gcdata_jobs/' . basename($jobId) . '.json';
        if (!is_file($file)) { return null; }
        $json = @file_get_contents($file);
        $data = json_decode($json ?: 'null', true);
        return is_array($data) ? $data : null;
    }
}


