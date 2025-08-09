<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Service\ExchangeRateService;
use App\NewShippingBundle\Repository\ZoneRepository;
use App\NewShippingBundle\Service\ShippingFeeService;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductFavorite;
use App\ProductBundle\Repository\ProductFavoriteRepository;
use App\ProductBundle\Repository\ProductHasAttributeRepository;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\CategoryService;
use App\ProductBundle\Service\FacebookShopService;
use App\ProductBundle\Service\ProductSearchService;
use App\ProductBundle\Service\ProductService;
use App\ShippingBundle\Service\ShippingService;
use App\UserBundle\Entity\User;
use PN\SeoBundle\Service\SeoService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/new")
 */
class ProductShowController extends AbstractController
{
    /**
     * @Route("/show/{slug}", name="fe_product_show", methods={"GET"})
     */
    public function index(
        Request                   $request,
        TranslatorInterface       $translator,
        SeoService                $seoService,
        FacebookShopService       $facebookShopService,
        ProductService            $productService,
        CategoryService           $categoryService,
        ProductPriceRepository    $productPriceRepository,
        ProductFavoriteRepository $productFavoriteRepository,
        ShippingService           $shippingService,
        string                    $slug
    ): Response
    {
        if ($this->isGranted("ROLE_ADMIN")) {
            $product = $seoService->getSlug($request, $slug, new Product());
        } else {
            $product = $productService->getBySlug($request, $slug);
        }

        if ($product instanceof RedirectResponse) {
            return $product;
        }
        if (!$product) {
            throw $this->createNotFoundException();
        }

        if ($product->getDeleted() instanceof \DateTime) {
            throw $this->createNotFoundException();
            //            return $this->render("ProductBundle:FrontEnd/Product:notAvailable.html.twig");
        }

        $productPrice = $productPriceRepository->getMinPrice($product);
        $zones = $shippingService->getZonesReadyToShipping();

        $isFavorite = false;
        if ($this->getUser() instanceof User) {
            $productFavorite = $productFavoriteRepository->findOneBy([
                'user' => $this->getUser(),
                'product' => $product,
            ]);
            if ($productFavorite instanceof ProductFavorite) {
                $isFavorite = true;
            }
        }


        return $this->render("product/frontEnd/productShow/index.html.twig", [
            "product" => $product,
            "breadcrumbs" => $this->showActionBreadcrumbs($translator, $categoryService, $product),
            "productPrice" => $productPrice,
            "zones" => $zones,
            "images" => $product->getPost()->getImages(),
            "isFavorite" => $isFavorite,
            "microdata" => $facebookShopService->getProductJSONMicrodata($product),
        ]);
    }

    /**
     * @Route("/product-description/{slug}", name="fe_product_description_ajax", methods={"GET"})
     */
    public function getProductDescription(
        Request             $request,
        TranslatorInterface $translator,
        SeoService          $seoService,
        ProductService      $productService,
        string              $slug
    ): Response
    {
        if ($this->isGranted("ROLE_ADMIN")) {
            $product = $seoService->getSlug($request, $slug, new Product(), redirect: false);
        } else {
            $product = $productService->getBySlug($request, $slug, false);
        }
        if (!$product) {
            throw $this->createNotFoundException();
        }

        if ($product->getDeleted() instanceof \DateTime) {
            throw $this->createNotFoundException();
        }

        return $this->render("product/frontEnd/productShow/_description_tab.html.twig", [
            "product" => $product,
        ]);
    }

    /**
     * @Route("/product-specs/{slug}", name="fe_product_specs_ajax", methods={"GET"})
     */
    public function getProductSpecs(
        Request                       $request,
        TranslatorInterface           $translator,
        SeoService                    $seoService,
        ProductService                $productService,
        ProductHasAttributeRepository $productHasAttributeRepository,
        string                        $slug
    ): Response
    {
        if ($this->isGranted("ROLE_ADMIN")) {
            $product = $seoService->getSlug($request, $slug, new Product(), redirect: false);
        } else {
            $product = $productService->getBySlug($request, $slug, false);
        }
        if (!$product) {
            throw $this->createNotFoundException();
        }

        if ($product->getDeleted() instanceof \DateTime) {
            throw $this->createNotFoundException();
        }

        $specsAsText = [];
        if ($product->getSku()) {
            $specsAsText["modelNo"] = [
                "title" => $translator->trans("sku_txt"),
                "value" => $product->getSku(),
                "url" => null,
            ];
        }
        if ($product->getBrand()) {
            $specsAsText["brand"] = [
                "title" => $translator->trans("brand_txt"),
                "value" => $product->getBrand()->getTitle(),
                "url" => $this->generateUrl("fe_product_filter_brand", ["slug" => $product->getBrand()->getSeo()->getSlug()]),
            ];
        }


        $specs = $productHasAttributeRepository->findProduct($product);
        foreach ($specs as $spec) {
            $attribute = $spec->getAttribute();
            $subAttribute = $spec->getSubAttribute();
            $value = ($spec->getOtherValue() !== null) ? $spec->getOtherValue() : $subAttribute->getTitle();

            if (array_key_exists($attribute->getId(), $specsAsText)) {
                $specsAsText[$attribute->getId()]['value'] = $specsAsText[$attribute->getId()]['value'] . ", " . $value;
            } else {
                $specsAsText[$attribute->getId()] = [
                    "title" => $attribute->getTitle(),
                    "value" => $value,
                    "url" => null,
                ];
            }
        }

        return $this->render("product/frontEnd/productShow/_specs_tab.html.twig", [
            "specs" => $specsAsText,
        ]);
    }

    /**
     * @Route("/related-products-section/{slug}", name="fe_product_related_products_ajax", methods={"GET"})
     */
    public function relatedProductsSection(
        Request                 $request,
        TranslatorInterface     $translator,
        SeoService              $seoService,
        ProductService          $productService,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository,
                                $slug
    ): Response
    {
        if ($this->isGranted("ROLE_ADMIN")) {
            $product = $seoService->getSlug($request, $slug, new Product(), redirect: false);
        } else {
            $product = $productService->getBySlug($request, $slug, false);
        }

        if (!$product) {
            return $this->json(["error" => true, "message" => $translator->trans("product_not_found_txt")]);
        }

        if ($product->getDeleted() instanceof \DateTime) {
            return $this->json(["error" => true, "message" => $translator->trans("product_not_found_txt")]);
        }

        $products = $this->getRelatedProducts($productSearchRepository, $product);

        $return = [
            "title" => [
                "title" => $translator->trans("related_items_txt"),
                "subTitle" => null,
                "icon" => null,
                "style" => 5,
                "actionBtn" => null,
            ],
            "products" => [],
        ];
        foreach ($products as $product) {
            $return["products"][] = $productSearchService->convertEntityToObject($product);
        }

        return $this->json($return);
    }

    /**
     * @Route("/recommended-for-you-section/{slug}", name="fe_product_recommended_for_you_products_ajax", methods={"GET"})
     */
    public function recommendedForYouSection(
        Request                 $request,
        TranslatorInterface     $translator,
        ProductSearchService    $productSearchService,
        SeoService              $seoService,
        ProductService          $productService,
        ProductSearchRepository $productSearchRepository,
        ?string                 $slug = null
    ): Response
    {
        $product = null;
        if ($slug != null) {
            if ($this->isGranted("ROLE_ADMIN")) {
                $product = $seoService->getSlug($request, $slug, new Product(), redirect: false);
            } else {
                $product = $productService->getBySlug($request, $slug, false);
            }

        }


        $search = new \stdClass();
        $search->ordr = ["column" => 0, "dir" => "DESC"];;
        //        $search->offer = true;
        $search->featured = true;
        $search->hasStock = true;
        if ($product instanceof Product) {
            $search->notId = $product->getId();
        }
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }

        $products = $productSearchRepository->filter($search, false, 0, 12);

        $return = [
            "title" => [
                "title" => $translator->trans("featured_items_txt"),
                "subTitle" => null,
                "icon" => null,
                "style" => 5,
                "actionBtn" => null,
            ],
            "products" => [],
        ];
        foreach ($products as $product) {
            $return["products"][] = $productSearchService->convertEntityToObject($product);
        }

        return $this->json($return);
    }

    /**
     * @Route("/calculate-shipping_price-ajax/{slug}", name="fe_shipping_address_calculate_shipping_price_ajax", methods={"POST"})
     */
    public function calculateProductShippingPrice(
        Request                $request,
        TranslatorInterface    $translator,
        SeoService             $seoService,
        ShippingFeeService     $shippingFeeService,
        ExchangeRateService    $exchangeRateService,
        ProductPriceRepository $productPriceRepository,
        ZoneRepository         $zoneRepository,
        string                 $slug
    )
    {
        $product = $seoService->getSlug($request, $slug, new Product());
        if ($product instanceof RedirectResponse) {
            return $product;
        }
        if (!$product) {
            return $this->json(['error' => 0, "message" => "Not Found"]);
        }

        if ($product->getDeleted()) {
            return $this->json(['error' => true, "message" => "Not Found"]);
        }

        $zoneId = $request->get('zoneId');
        if (!Validate::not_null($zoneId)) {
            return $this->json(['error' => true, "message" => "Not Found"]);
        }
        $zone = $zoneRepository->find($zoneId);
        if (!$zone) {
            return $this->json(['error' => true, "message" => "City Not available"]);
        }

        $productPrice = $productPriceRepository->getMinPrice($product);
        $currency = $this->em()->getRepository(Currency::class)->findOneBy([]);
        $shippingFees = $shippingFeeService->calculateShippingFeesByProductPrice($productPrice, $zone, $currency, 1);

        if ($shippingFees != null) {
            $moneyFormat = $exchangeRateService->moneyFormat($shippingFees);

            $return = [
                'error' => false,
                "message" => null,
                'price' => $moneyFormat,
//                "shippingTime" => implode(", ", $availableShippingTimesArray)
                "shippingTime" => ""
            ];
        } else {
            $message = $translator->trans("cannot_ship_to_zone_msg");
            $return = ['error' => true, "message" => $message];
        }

        return $this->json($return);
    }

    private function showActionBreadcrumbs(
        TranslatorInterface $translator,
        CategoryService     $categoryService,
        Product             $product
    ): array
    {
        $categoryParents = $categoryService->parentsByChild($product->getCategory());

        $breadcrumbs = [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
        ];
        foreach ($categoryParents as $categoryParent) {
            $breadcrumbs[] = [
                "title" => $categoryParent->getTitle(),
                "url" => $this->generateUrl("fe_category_index", ["slug" => $categoryParent->getSeo()->getSlug()]),
            ];
        }
        $breadcrumbs[] = [
            "title" => $product->getTitle(),
            "url" => null,
        ];

        return $breadcrumbs;
    }

    private function getRelatedProducts(
        ProductSearchRepository $productSearchRepository,
        Product                 $product,
    ): array
    {
        $relatedProducts = $product->getDetails()->getRelatedProducts();
        if (count($relatedProducts) < 1) {
            return $productSearchRepository->getRelatedProducts($product, 21);
        }
        $relatedProductIds = [];
        foreach ($relatedProducts as $relatedVendorProduct) {
            $relatedProductIds[] = $relatedVendorProduct->getId();
        }

        $search = new \stdClass();
        $search->ordr = 0;
        $search->ids = $relatedProductIds;
        $search->notId = $product->getId();
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }
        return $productSearchRepository->filter($search, false, 0, 21);
    }
}
