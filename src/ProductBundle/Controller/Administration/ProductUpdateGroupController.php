<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CurrencyBundle\Entity\Currency;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Entity\CouponHasProduct;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Form\Filter\ProductFilterType;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductService;
use App\ProductBundle\Service\ProductSearchService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Lib\Paginator;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Product controller.
 *
 * @Route("/update-group")
 */
class ProductUpdateGroupController extends AbstractController
{

    private ?string $username = null;
    private UserService $userService;
    private ProductSearchService $productSearchService;

    public function __construct(EntityManagerInterface $em, UserService $userService, ProductSearchService $productSearchService)
    {
        parent::__construct($em);
        $this->userService = $userService;
        $this->productSearchService = $productSearchService;
    }

    /**
     * Lists all product entities.
     *
     * @Route("/{page}", requirements={"page" = "\d+"}, name="product_group_update_index", methods={"GET"})
     */
    public function index(
        Request $request,
        ProductRepository $productRepository,
        ProductService $productService,
        $page = 1
    ): Response {
        $filterForm = $this->createForm(ProductFilterType::class);
        $filterForm->handleRequest($request);
        $search = $productService->collectSearchData($filterForm);


        $count = $productRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 50);
        $products = $productRepository->filter($search, $count = false,
            $paginator->getLimitStart(), $paginator->getPageLimit());

        return $this->render('product/admin/productUpdateGroup/index.html.twig', [
            'search' => $search,
            "filter_form" => $filterForm->createView(),
            'products' => $products,
            'paginator' => $paginator->getPagination(),
        ]);
    }

    /**
     * @Route("/group-update/action", name="product_group_update_action", methods={"GET"})
     */
    public function groupUpdate(
        Request $request,
        ProductService $productService,
        ProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);


        $session = $request->getSession();

        if ($session->has('productSelected') and count($session->get('productSelected')) > 0) {
            $productSelected = $session->get('productSelected');
            $search = new \stdClass();
            $search->ids = $productSelected;
            $products = $productRepository->filter($search, false);
        } elseif ($request->get('filter') == '1') {
            $filterForm = $this->createForm(ProductFilterType::class);
            $filterForm->handleRequest($request);
            $search = $productService->collectSearchData($filterForm);
            $products = $productRepository->filter($search, false);

            $productSelected = [];
            foreach ($products as $product) {
                $productSelected[] = $product->getId();
            }
            $session->set('productSelected', $productSelected);
        } else {
            $this->addFlash('error', 'Please add some products');

            return $this->redirectToRoute('product_group_update_index');
        }


        return $this->render("product/admin/productUpdateGroup/groupUpdate.html.twig", [
            'products' => $products,
        ]);
    }

    /**
     * Lists all Product entities.
     *
     * @Route("/group-update/session-ajax", name="product_group_update_session_ajax", methods={"POST"})
     */
    public function productGroupUpdateSessionAjax(Request $request): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $session = $request->getSession();
        $id = $request->request->get('id');
        if ($session->has('productSelected')) {
            $productSelected = $session->get('productSelected');
        } else {
            $productSelected = [];
        }

        if (is_numeric($id)) {
            if (!in_array($id, $productSelected)) { // add product in array
                array_push($productSelected, $id);
            } else { // remove product in array
                $key = array_search($id, $productSelected);
                unset($productSelected[$key]);
            }
        } else {
            $type = $request->request->get('type');
            $ids = json_decode($id);
            foreach ($ids as $id) {
                if (isset($type) and $type == 'add') {
                    if (!in_array($id, $productSelected)) {
                        array_push($productSelected, $id);
                    }
                } else {
                    if (in_array($id, $productSelected)) {
                        $key = array_search($id, $productSelected);
                        unset($productSelected[$key]);
                    }
                }
            }
        }
        $session->set('productSelected', $productSelected);

        return $this->json($productSelected);
    }

    /**
     * Add a Product Price entity.
     *
     * @Route("/group-update/update", name="product_group_update_action_update", methods={"POST"})
     */
    public function productGroupUpdate(Request $request,ProductRepository $productRepository, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $session = $request->getSession();


        $type = $request->request->get('type');
        $data = $request->request->get('data');


        if (!Validate::not_null($type)) {
            $this->addFlash('error', 'Error in Type');

            return $this->redirectToRoute('product_group_update_action');
        }

        $productSelected = $session->get('productSelected');
        if (count($productSelected) == 0) {
            $this->addFlash('error', 'Please add some products');

            return $this->redirectToRoute('product_group_update_action');
        }

        $entities = [];
        foreach ($productSelected as $value) {
            if (Validate::not_null($value)) {
                $product = $productRepository->find($value);
                $entities[] = $product;
            }
        }
        $userName = $userService->getUserName();
        $n = 0;
        if ($type == 'checkbox') {
            foreach ($entities as $entity) {
                $n++;
                $this->updateCheckboxes($entity, $data);
            }
            
            // Re-index products after checkbox updates
            $this->reindexUpdatedProducts($entities);
        } elseif ($type == 'promotion') {
            if (!isset($data['removeDiscount'])) {
                if (isset($data['discount']) and (!Validate::not_null($data['discount']) or !is_numeric($data['discount']))) {
                    $this->addFlash('error', 'Please enter the promotion discount');

                    return $this->redirectToRoute('product_group_update_action');
                }
                if (Validate::not_null($data['expiryDate']) and !Validate::date($data['expiryDate'])) {
                    $this->addFlash('error', 'Please enter a valid promotion expiry date');

                    return $this->redirectToRoute('product_group_update_action');
                }
            }
            $promotionalExpiryDate = null;
            if (isset($data['expiryDate']) and Validate::not_null($data['expiryDate'])) {
                $promotionalExpiryDate = Date::convertDateFormat($data['expiryDate'], Date::DATE_FORMAT3,
                    Date::DATE_FORMAT2);
                $promotionalExpiryDate = new \DateTime($promotionalExpiryDate);
            }

            foreach ($entities as $entity) {
                $n++;
                $this->updatePromotionPrice($entity, $data, $promotionalExpiryDate);
            }
            
            // Re-index products after promotion updates
            $this->reindexUpdatedProducts($entities);
        } elseif ($type == 'content') {
            foreach ($entities as $entity) {
                $n++;
                $this->updateContent($entity, $data);
            }
            
            // Re-index products after content updates
            $this->reindexUpdatedProducts($entities);
        } elseif ($type == 'price') {

            

            
            if (!isset($data['priceUpdateType']) or !Validate::not_null($data['priceUpdateType'])) {
                $this->addFlash('error', 'Please select price update type');
                return $this->redirectToRoute('product_group_update_action');
            }
            if (!isset($data['priceUpdateValue']) or !Validate::not_null($data['priceUpdateValue']) or !is_numeric($data['priceUpdateValue'])) {
                $this->addFlash('error', 'Please enter a valid price update value');
                return $this->redirectToRoute('product_group_update_action');
            }
            // Currency field removed - we only update the price without currency selection
            
            // Additional validation for price updates
            $updateValue = (float) $data['priceUpdateValue'];
            $updateType = $data['priceUpdateType'];
            
            if ($updateType === '+percentage' && ($updateValue < 0 || $updateValue > 1000)) {
                $this->addFlash('error', 'Percentage increase must be between 0 and 1000');
                return $this->redirectToRoute('product_group_update_action');
            }
            
            if ($updateType === '-percentage' && ($updateValue < 0 || $updateValue > 100)) {
                $this->addFlash('error', 'Percentage decrease must be between 0 and 100');
                return $this->redirectToRoute('product_group_update_action');
            }
            
            if ($updateType === '-fixed' && $updateValue < 0) {
                $this->addFlash('error', 'Fixed amount to subtract cannot be negative');
                return $this->redirectToRoute('product_group_update_action');
            }
            
            // Validate promotion data
            if (isset($data['promotionDiscount']) && Validate::not_null($data['promotionDiscount'])) {
                $discount = (float) $data['promotionDiscount'];
                if ($discount < 0 || $discount > 100) {
                    $this->addFlash('error', 'Promotion discount must be between 0 and 100');
                    return $this->redirectToRoute('product_group_update_action');
                }
                
                if (!isset($data['promotionExpiry']) || !Validate::not_null($data['promotionExpiry'])) {
                    $this->addFlash('error', 'Promotion discount requires an expiry date');
                    return $this->redirectToRoute('product_group_update_action');
                }
                
                if (!Validate::date($data['promotionExpiry'])) {
                    $this->addFlash('error', 'Please enter a valid promotion expiry date');
                    return $this->redirectToRoute('product_group_update_action');
                }
            }
            
            foreach ($entities as $entity) {
                $n++;
                $this->updateProductPrices($entity, $data);
            }
            
            // Re-index the updated products to sync product_search table
            $this->reindexUpdatedProducts($entities);
        } elseif ($type == 'stock') {
            if (!$this->validateStockData($data)) {
                return $this->redirectToRoute('product_group_update_action');
            }
            
            foreach ($entities as $entity) {
                $n++;
                $this->updateProductStock($entity, $data);
            }
        } elseif ($type == 'dimensions') {
            if (!$this->validateDimensionsData($data)) {
                return $this->redirectToRoute('product_group_update_action');
            }
            
            foreach ($entities as $entity) {
                $n++;
                $this->updateProductDimensions($entity, $data);
            }
        }
        
        // Flush all changes to database first
        try {
            $this->em()->flush();
        } catch (\Exception $e) {
            error_log("Database flush failed: " . $e->getMessage());
            $this->addFlash('error', 'Database update failed: ' . $e->getMessage());
            return $this->redirectToRoute('product_group_update_action');
        }
        
        // Then re-index the updated products
        if ($type == 'price' || $type == 'stock' || $type == 'dimensions') {
            $this->reindexUpdatedProducts($entities);
        }

        $this->addFlash('success', $n.' Products updated successfully');
        if ($request->request->get('action') == "saveAndNext") {
            return $this->redirectToRoute('product_group_update_action');
        }
        $session->remove('productSelected');

        return $this->redirectToRoute('product_group_update_index');
    }

    private function getUsername(): ?string
    {
        if ($this->username == null) {
            return $this->username = $this->userService->getUserName();
        }

        return $this->username;
    }

    private function updateCheckboxes(Product $product, $data)
    {

        if (isset($data['featured'])) {
            $product->setFeatured(true);
        } elseif (isset($data['notFeatured'])) {
            $product->setFeatured(false);
        }
        if (isset($data['publish'])) {
            $product->setPublish(true);
        } elseif (isset($data['unpublish'])) {
            $product->setPublish(false);
        }

        if (isset($data['premium'])) {
            $product->setPremium(true);
        } elseif (isset($data['notPremium'])) {
            $product->setPremium(false);
        }

        if (isset($data['newArrival'])) {
            $product->setNewArrival(true);
        } elseif (isset($data['notNewArrival'])) {
            $product->setNewArrival(false);
        }
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    private function updateCoupon(Product $product, Coupon $coupon)
    {
        $checkCouponHasProduct = $this->em()->getRepository('ECommerceBundle:Coupon')->checkCouponHasProduct($coupon->getId(),
            $product->getId());
        if (!$checkCouponHasProduct) {
            $couponHasProduct = new CouponHasProduct();
            $couponHasProduct->setCoupon($coupon);
            $couponHasProduct->setProduct($product);
            $coupon->addCouponHasProduct($couponHasProduct);
            $this->em()->persist($coupon);
        }
    }

    private function updatePromotionPrice(Product $product, $data, \DateTime $promotionalExpiryDate = null)
    {
        // This method is kept for compatibility but promotion handling is now done in updateProductPrices
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    private function updateContent(Product $product, $data)
    {


        $content = $product->getPost()->getContent();

        if (!isset($data['disabledDescription'])) {
            $content['description'] = $data['description'];
        }


        if (!isset($data['disabledBrief']) and array_key_exists("brief", $data)) {
            $content['brief'] = $data['brief'];
        }

        $product->getPost()->setContent($content);
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    private function updateProductPrices(Product $product, $data)
    {
        $updateType = $data['priceUpdateType'];
        $updateValue = (float) $data['priceUpdateValue'];
        
        // Get all available currencies to ensure we update/create prices for all
        $currencies = $this->em()->getRepository(Currency::class)->findAll();
        if (empty($currencies)) {
            error_log("No currencies found for price update");
            return;
        }
        
        // Update unit prices - for all currencies
        $updatedPrices = 0;
        foreach ($currencies as $currency) {
            // Find existing price for this currency, or create new one
            $price = $this->findOrCreateProductPrice($product, $currency);
            
            $currentPrice = $price->getUnitPrice() ?? 0;
            $newPrice = $currentPrice;
            
            switch ($updateType) {
                case '+percentage':
                    // Handle percentage increase
                    $newPrice = $currentPrice + ($currentPrice * $updateValue / 100);
                    break;
                case '-percentage':
                    // Handle percentage decrease
                    $newPrice = $currentPrice - ($currentPrice * $updateValue / 100);
                    break;
                case '+fixed':
                    // Handle fixed amount increase
                    $newPrice = $currentPrice + $updateValue;
                    break;
                case '-fixed':
                    // Handle fixed amount decrease
                    $newPrice = max(0, $currentPrice - $updateValue);
                    break;
            }
            
            // Ensure price is not negative
            $newPrice = max(0, $newPrice);
            
            $price->setUnitPrice($newPrice);
            $this->em()->persist($price);
            $updatedPrices++;
        }
        
        // Handle promotion prices - for all currencies
        if (isset($data['removePromotion']) && $data['removePromotion']) {
            // Remove all promotions for all currencies
            foreach ($currencies as $currency) {
                $price = $this->findOrCreateProductPrice($product, $currency);
                $price->setPromotionalPrice(null);
                $price->setPromotionalExpiryDate(null);
                $this->em()->persist($price);
            }
        } elseif (isset($data['promotionDiscount']) && Validate::not_null($data['promotionDiscount'])) {
            $discount = (float) $data['promotionDiscount'];
            $promotionalExpiryDate = null;
            
            if (isset($data['promotionExpiry']) && Validate::not_null($data['promotionExpiry'])) {
                $promotionalExpiryDate = Date::convertDateFormat($data['promotionExpiry'], Date::DATE_FORMAT3, Date::DATE_FORMAT2);
                $promotionalExpiryDate = new \DateTime($promotionalExpiryDate);
            }
            
            if ($promotionalExpiryDate) {
                foreach ($currencies as $currency) {
                    $price = $this->findOrCreateProductPrice($product, $currency);
                    $unitPrice = $price->getUnitPrice() ?? 0;
                    $promotionalPrice = $unitPrice - ($unitPrice * $discount / 100);
                    $price->setPromotionalPrice($promotionalPrice);
                    $price->setPromotionalExpiryDate($promotionalExpiryDate);
                    $this->em()->persist($price);
                }
            }
        }
        
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    /**
     * Re-index updated products to sync product_search table
     */
    private function reindexUpdatedProducts(array $products): void
    {
        try {
            foreach ($products as $product) {
                // Re-index each updated product
                $this->productSearchService->insertOrDeleteProductInSearch($product);
            }
        } catch (\Exception $e) {
            error_log("Error during product re-indexing: " . $e->getMessage());
            // Don't throw the exception - we don't want to fail the price update
            // Just log the error for debugging
        }
    }

    /**
     * Update product stock information
     * Stock fields are stored in ProductPrice entities (one for each currency)
     * Creates ProductPrice entities for all currencies if they don't exist
     */
    private function updateProductStock(Product $product, $data)
    {
        $stockUpdateType = $data['stockUpdateType'];
        $stockUpdateValue = (int) $data['stockUpdateValue'];
        
        // Get all available currencies
        $currencies = $this->em()->getRepository(Currency::class)->findAll();
        if (empty($currencies)) {
            error_log("No currencies found for stock update");
            return;
        }
        
        $updatedPrices = 0;
        
        foreach ($currencies as $currency) {
            // Find existing price for this currency, or create new one
            $price = $this->findOrCreateProductPrice($product, $currency);
            
            $currentStock = $price->getStock() ?? 0;
            $newStock = $this->calculateNewStock($currentStock, $stockUpdateType, $stockUpdateValue);
            
            $price->setStock($newStock);
            $this->em()->persist($price);
            $updatedPrices++;
            
        }
        
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    /**
     * Update product dimensions and weight
     * Weight, length, width, and height fields are stored in ProductPrice entities
     * Creates ProductPrice entities for all currencies if they don't exist
     */
    private function updateProductDimensions(Product $product, $data)
    {
        $updatedPrices = 0;
        
        // Get all available currencies
        $currencies = $this->em()->getRepository(Currency::class)->findAll();
        if (empty($currencies)) {
            error_log("No currencies found for dimensions update");
            return;
        }
        
        foreach ($currencies as $currency) {
            // Find existing price for this currency, or create new one
            $price = $this->findOrCreateProductPrice($product, $currency);
            $hasChanges = false;
            
            // Update weight if provided
            if (isset($data['productWeight']) && Validate::not_null($data['productWeight'])) {
                $weight = (float) $data['productWeight'];
                $price->setWeight($weight);
                $hasChanges = true;
            }
            
            // Update length if provided
            if (isset($data['productLength']) && Validate::not_null($data['productLength'])) {
                $length = (float) $data['productLength'];
                $price->setLength($length);
                $hasChanges = true;
            }
            
            // Update width if provided
            if (isset($data['productWidth']) && Validate::not_null($data['productWidth'])) {
                $width = (float) $data['productWidth'];
                $price->setWidth($width);
                $hasChanges = true;
            }
            
            // Update height if provided
            if (isset($data['productHeight']) && Validate::not_null($data['productHeight'])) {
                $height = (float) $data['productHeight'];
                $price->setHeight($height);
                $hasChanges = true;
            }
            
            if ($hasChanges) {
                $this->em()->persist($price);
                $updatedPrices++;
            }
        }
        
        $product->setModifiedBy($this->getUsername());
        $this->em()->persist($product);
    }

    /**
     * Validate stock update data
     */
    private function validateStockData($data): bool
    {
        if (!isset($data['stockUpdateType']) || !Validate::not_null($data['stockUpdateType'])) {
            $this->addFlash('error', 'Please select stock update type');
            return false;
        }
        
        if (!isset($data['stockUpdateValue']) || !Validate::not_null($data['stockUpdateValue']) || !is_numeric($data['stockUpdateValue'])) {
            $this->addFlash('error', 'Please enter a valid stock value');
            return false;
        }
        
        if ((int) $data['stockUpdateValue'] < 0) {
            $this->addFlash('error', 'Stock value cannot be negative');
            return false;
        }
        
        return true;
    }

    /**
     * Validate dimensions update data
     */
    private function validateDimensionsData($data): bool
    {
        $hasData = false;
        $dimensionFields = ['productLength', 'productWidth', 'productHeight', 'productWeight'];
        
        foreach ($dimensionFields as $field) {
            if (isset($data[$field]) && Validate::not_null($data[$field])) {
                $hasData = true;
                break;
            }
        }
        
        if (!$hasData) {
            $this->addFlash('error', 'Please enter at least one dimension or weight value');
            return false;
        }
        
        foreach ($dimensionFields as $field) {
            if (isset($data[$field]) && Validate::not_null($data[$field])) {
                if (!is_numeric($data[$field]) || (float) $data[$field] < 0) {
                    $fieldName = ucfirst(str_replace('product', '', $field));
                    $this->addFlash('error', $fieldName . ' must be a positive number');
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Calculate new stock value based on update type
     */
    private function calculateNewStock(int $currentStock, string $updateType, int $updateValue): int
    {
        return match ($updateType) {
            'set' => $updateValue,
            'add' => $currentStock + $updateValue,
            'subtract' => max(0, $currentStock - $updateValue),
            default => $currentStock,
        };
    }

    /**
     * Find existing ProductPrice for a product and currency, or create new one
     */
    private function findOrCreateProductPrice(Product $product, $currency): ProductPrice
    {
        // First try to find existing price for this currency
        $existingPrice = $this->em()->getRepository(ProductPrice::class)->findOneBy([
            'product' => $product,
            'currency' => $currency,
            'deleted' => null
        ]);
        
        if ($existingPrice) {
            return $existingPrice;
        }
        
        // Create new ProductPrice if none exists
        $newPrice = new \App\ProductBundle\Entity\ProductPrice();
        $newPrice->setProduct($product);
        $newPrice->setCurrency($currency);
        $newPrice->setUnitPrice(0); // Default price, will be updated later if needed
        $newPrice->setStock(0); // Default stock
        $newPrice->setWeight(0); // Default weight
        
        // Set required fields
        $newPrice->setCreator($this->getUsername());
        $newPrice->setModifiedBy($this->getUsername());
        
        // Add to product's prices collection
        $product->getPrices()->add($newPrice);
        
        return $newPrice;
    }



}
