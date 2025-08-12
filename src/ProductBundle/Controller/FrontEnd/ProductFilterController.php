<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\CategoryService;
use App\ProductBundle\Service\ProductFilterService;
use App\ProductBundle\Service\ProductSearchService;
use App\UserBundle\Entity\User;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use PN\ServiceBundle\Lib\Paginator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/filter")
 */
class ProductFilterController extends AbstractController
{

    const LOAD_CONTENT_AJAX = true;

    /**
     * @Route("/search/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_search", methods={"GET", "POST"})
     * @Route("/sale/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_sale", methods={"GET", "POST"})
     * @Route("/filter/{slug}/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_category", methods={"GET", "POST"})
     * @Route("/collection/{slug}/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_collection", methods={"GET", "POST"})
     * @Route("/occasion/{slug}/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_occasion", methods={"GET", "POST"})
     * @Route("/brand/{slug}/{page}/{sort}", requirements={"page" = "\d+","sort" = "\d+"}, name="fe_product_filter_brand", methods={"GET", "POST"})
     */
    public function index(
        Request                 $request,
        TranslatorInterface     $translator,
        Packages                $assets,
        SeoPageRepository       $seoPageRepository,
        SeoService              $seoService,
        ProductSearchService    $productSearchService,
        CategoryService         $categoryService,
        ProductFilterService    $productFilterService,
        ProductSearchRepository $productSearchRepository,
        int                     $page = 1,
        ?string                 $slug = null,
        int                     $sort = 1
    ): Response
    {
        $slugObject = $this->getSlugObject($request, $seoService, $slug);
        if (is_array($slugObject) and $slugObject['redirect'] instanceof RedirectResponse) {
            return $slugObject['redirect'];
        }
        $search = $this->collectParams($request, $slugObject);

        $filters = $products = [];
        $paginator = null;
        $count = 0;
        if (!self::LOAD_CONTENT_AJAX or $request->isMethod("POST")) {
            $count = $productSearchRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 24);
            $products = ($count > 0) ? $productSearchRepository->filter($search, false,
                $paginator->getLimitStart(), $paginator->getPageLimit()) : [];

            $filters = $this->getFilters($request, $productFilterService, $productSearchRepository, $slugObject,
                $search, $count);
        }

        if ($request->isMethod("POST")) { //ajax
            $productsObjects = [];
            foreach ($products as $product) {
                $productsObjects[] = $productSearchService->convertEntityToObject($product);
            }

            $paginationRendered = $this->renderView("fe/_pagination.html.twig", [
                "paginator" => $paginator->getPagination(),
                "queryParams" => $request->request->all(),
            ]);

            $filterRendered = $this->renderView("product/frontEnd/productFilter/_filter.html.twig", [
                "filters" => $filters,
                "loadByAjax" => true,
                "loadContentAjax" => self::LOAD_CONTENT_AJAX,
            ]);

            return $this->json([
                "noOfProducts" => $count,
                "products" => $productsObjects,
                "paginationHTML" => $paginationRendered,
                "filterHTML" => $filterRendered,
            ]);
        }

        return $this->render("product/frontEnd/productFilter/index.html.twig", [
            'slugObject' => $slugObject,
            'seoPage' => $this->getSeoPage($request, $seoPageRepository, $slugObject),
            "headerData" => $this->getHeaderData($request, $assets, $translator, $search,
                $count, $slugObject),
            "breadcrumbs" => $this->getPageBreadcrumbs($request, $translator, $categoryService, $slugObject),
            "sortBy" => $this->getSortBy($request, $translator),
            "paginator" => ($paginator instanceof Paginator) ? $paginator->getPagination() : null,
            "products" => $products,
            "filters" => $filters,
            "loadContentAjax" => self::LOAD_CONTENT_AJAX,
        ]);
    }

    // Done
    private function getSeoPage(Request $request, SeoPageRepository $seoPageRepository, ?array $slugObject)
    {
        $routeName = $request->attributes->get('_route');
        $seoData = null;

        if ($routeName == "fe_product_filter_sale") {
            $seoData = $seoPageRepository->findOneByType("sale");
        }
        if (is_array($slugObject) and method_exists($slugObject['entity'], "getSeo")) {
            $seoData = $slugObject['entity'];
        }

        if ($seoData === null) {
            $seoData = $seoPageRepository->findOneByType("products");
        }

        return $seoData;
    }

    // Done
    private function getHeaderData(
        Request             $request,
        Packages            $assets,
        TranslatorInterface $translator,
        \stdClass           $search,
        int                 $numberOfProducts,
        ?array              $slugObject
    ): array
    {
        $pageTitle = $translator->trans("products_txt");
        $description = null;

        if (is_array($slugObject) and method_exists($slugObject['entity'], "getTitle")) {
            $pageTitle = $slugObject['entity']->getTitle();
        }

        if ($slugObject != null and isset($slugObject['entity']->getPost()->getContent()['brief'])) {
            $description = $slugObject['entity']->getPost()->getContent()['brief'];
        }

        $routeName = $request->attributes->get('_route');
        if ($routeName == "fe_product_filter_sale") {
            $pageTitle = $translator->trans("on_sale_txt");
        }

        return [
            "pageTitle" => $pageTitle,
            "description" => $description,
            "bannerImageUrl" => $this->getHeaderDataBanner($request, $assets, $search, $slugObject),
            "numberOfProducts" => $numberOfProducts,
        ];
    }


    private function getHeaderDataBanner(
        Request   $request,
        Packages  $assets,
        \stdClass $search,
        ?array    $slugObject
    ): ?string
    {
        $bannerImageUrl = null;

        if (
            is_array($slugObject)
            and in_array($slugObject['class'], [Collection::class, Occasion::class, Category::class])
        ) {
            if (method_exists($slugObject['entity'], "getPost")) {
                $coverPhoto = $slugObject['entity']->getPost()->getImageByType(Image::TYPE_COVER_PHOTO);
                if ($coverPhoto instanceof Image) {
                    $bannerImageUrl = $assets->getUrl($coverPhoto->getAssetPath());
                }
            }
        }


//$routeName = $request->attributes->get('_route');
//$locale = $request->attributes->get('_locale');


        return $bannerImageUrl;
    }

    // Done
    private function getPageBreadcrumbs(
        Request             $request,
        TranslatorInterface $translator,
        CategoryService     $categoryService,
        ?array              $slugObject
    ): array
    {
        $breadcrumbs = [
            [
                "title" => $translator->trans("home_txt"),
                "url" => $this->generateUrl("fe_home"),
            ],
        ];


        if (is_array($slugObject) and $slugObject['class'] == Category::class) {
            $categoryParents = $categoryService->parentsByChild($slugObject['entity']);
            $breadcrumbs[] = [
                "title" => $translator->trans('categories_txt'),
                "url" => $this->generateUrl("fe_category_index"),
            ];
            foreach ($categoryParents as $category) {
                if ($slugObject['entity']->getId() == $category->getId()) {
                    continue;
                }

                $breadcrumbs[] = [
                    "title" => $category->getTitle(),
                    "url" => $this->generateUrl("fe_category_index", ["slug" => $category->getSeo()->getSlug()]),
                ];
            }
            $breadcrumbs[] = [
                "title" => $slugObject['entity']->getTitle(),
                "url" => null,
            ];
        } elseif (is_array($slugObject) and $slugObject['class'] == Collection::class) {
            $breadcrumbs[] = [
                "title" => $translator->trans("collections_txt"),
                "url" => $this->generateUrl("fe_collection_index"),
            ];
            $breadcrumbs[] = [
                "title" => $slugObject['entity']->getTitle(),
                "url" => null,
            ];
        } elseif (is_array($slugObject) and $slugObject['class'] == Occasion::class) {
            $breadcrumbs[] = [
                "title" => $slugObject['entity']->getTitle(),
                "url" => null,
            ];
        } elseif (is_array($slugObject) and $slugObject['class'] == Brand::class) {
            $breadcrumbs[] = [
                "title" => $translator->trans('brands_txt'),
                "url" => $this->generateUrl("fe_brand_index"),
            ];
            $breadcrumbs[] = [
                "title" => $slugObject['entity']->getTitle(),
                "url" => null,
            ];
        }
        $routeName = $request->attributes->get('_route');
        if ($routeName == "fe_product_filter_sale") {
            $breadcrumbs[] = [
                "title" => $translator->trans("on_sale_txt"),
                "url" => null,
            ];
        } elseif (count($breadcrumbs) == 1) {
            $breadcrumbs[] = [
                "title" => $translator->trans("products_txt"),
                "url" => null,
            ];
        }

        return $breadcrumbs;
    }

    private function getFilters(
        Request                 $request,
        ProductFilterService    $productFilterService,
        ProductSearchRepository $productSearchRepository,
        ?array                  $slugObject,
        \stdClass               $search = null,
        int                     $numberOfProducts = 0
    ): array
    {

        $specs = $this->getSpecs($productSearchRepository, $search, $slugObject);
        $brands = $this->getBrands($request, $productSearchRepository, $search);
        $categories = $this->getCategories($request, $productSearchRepository, $search);

        return $productFilterService->frontEndConvertFilterToArray(
            request: $request,
            numberOfProducts: $numberOfProducts,
            search: $search,
            specs: $specs,
            categories: $categories,
            brands: $brands
        );


    }

    //Done
    private function getSortBy(Request $request, TranslatorInterface $translator): array
    {
        $routeName = $request->attributes->get('_route');
        $generateUrl = function (int $sortNumber) use ($request, $routeName) {
            $routeParameters = $request->attributes->get('_route_params');
            $queryParameters = $request->query->all();

            $params = array_merge($routeParameters, $queryParameters, ["page" => 1, "sort" => $sortNumber]);

            return $this->generateUrl($routeName, $params);
        };

        $getCurrentSortTitle = function (int $currentSortNumber, array $sortTypes) {
            foreach ($sortTypes as $sortType) {
                if ($sortType['sortNumber'] == $currentSortNumber) {
                    return $sortType['title'];
                }
            }

            return $sortTypes[0]['title'];
        };

        $sortTypes = [
            [
                "sortNumber" => 1,
                "title" => $translator->trans("product_filter_sort_recommended_txt"),
            ],
            [
                "sortNumber" => 2,
                "title" => $translator->trans("product_filter_sort_recently_added_txt"),
            ],
            [
                "sortNumber" => 3,
                "title" => $translator->trans("product_filter_sort_price_low_to_high_txt"),
            ],
            [
                "sortNumber" => 4,
                "title" => $translator->trans("product_filter_sort_price_high_to_low_txt"),
            ],
            [
                "sortNumber" => 5,
                "title" => $translator->trans("product_filter_sort_discount_high_to_low_txt"),
            ],
            [
                "sortNumber" => 6,
                "title" => $translator->trans("product_filter_sort_discount_low_to_high_txt"),
            ]
        ];


        $currentSortNumber = $request->get("sort");

        $sorts = [
            "currentSortNumber" => $currentSortNumber,
            "currentSortTitle" => $getCurrentSortTitle($currentSortNumber, $sortTypes),
            "types" => [],
        ];
        foreach ($sortTypes as $sortType) {
            $sortNumber = $sortType['sortNumber'];
            $title = $sortType['title'];

            $sorts["types"][] = [
                "sortNumber" => $sortNumber,
                "title" => $title,
                "url" => $generateUrl($sortNumber),
                "isSelected" => $currentSortNumber == $sortNumber,
            ];
        }

        return $sorts;
    }

    private function getSpecs(
        ProductSearchRepository $productSearchRepository,
        \stdClass               $search = null,
        ?array                  $slugObject
    ): array
    {
        if (
            is_array($slugObject) and $slugObject['class'] != Category::class
            or $slugObject == null
        ) {
            return [];
        }
        $category = $slugObject['entity'];

        $cloneSearch = clone $search;
        if (isset($cloneSearch->specs)) {
            $cloneSearch->specs = null;
        }
        return $productSearchRepository->getSpecsByCategory($category, $cloneSearch);
    }

    private function getCategories(
        Request                 $request,
        ProductSearchRepository $productSearchRepository,
        \stdClass               $search = null
    ): array
    {
        $routeName = $request->attributes->get('_route');


        switch ($routeName) {
            case "fe_product_filter_collection":
            case "fe_product_filter_occasion":
            case "fe_product_filter_brand":
            case "fe_product_filter_sale":
                $cloneSearch = clone $search;
                if (isset($cloneSearch->categories)) {
                    $cloneSearch->categories = null;
                }
                break;
            default:
                return [];
        }

        return $productSearchRepository->getCategoriesByFilter($cloneSearch);
    }

    private function getBrands(
        Request                 $request,
        ProductSearchRepository $productSearchRepository,
        \stdClass               $search = null
    ): array
    {
        $routeName = $request->attributes->get('_route');
        if ($routeName != "fe_product_filter_brand") {

            return $productSearchRepository->getBrandsByFilter($search);
        }

        return [];
    }

    private function getSlugObject(Request $request, SeoService $seoService, $slug): ?array
    {
        $routeName = $request->attributes->get('_route');

        $return = null;

        $returnFn = function ($class, $entity, $redirect = null) {
            return [
                "redirect" => $redirect,
                "class" => $class,
                "entity" => $entity,
            ];
        };

        switch ($routeName) {
            case "fe_product_filter_category":
                $category = $seoService->getSlug($request, $slug, new Category());
                if ($category instanceof RedirectResponse) {
                    return $returnFn(Category::class, null, $category);
                }
                if (!$category) {
                    throw $this->createNotFoundException();
                }
                if ($category->isDeleted()) {
                    throw $this->createNotFoundException();
                }
                $return = $returnFn(Category::class, $category);
                break;
            case "fe_product_filter_collection":
                $collection = $seoService->getSlug($request, $slug, new Collection());
                if ($collection instanceof RedirectResponse) {
                    return $returnFn(Collection::class, null, $collection);
                }
                if (!$collection) {
                    throw $this->createNotFoundException();
                }
                $return = $returnFn(Collection::class, $collection);
                break;
            case "fe_product_filter_occasion":
                $occasion = $seoService->getSlug($request, $slug, new Occasion());
                if ($occasion instanceof RedirectResponse) {
                    return $returnFn(Collection::class, null, $occasion);
                }
                if (!$occasion) {
                    throw $this->createNotFoundException();
                }
                $return = $returnFn(Occasion::class, $occasion);
                break;
            case "fe_product_filter_brand":
                $brand = $seoService->getSlug($request, $slug, new Brand());
                if ($brand instanceof RedirectResponse) {
                    return $returnFn(Brand::class, null, $brand);
                }
                if (!$brand) {
                    throw $this->createNotFoundException();
                }
                $return = $returnFn(Brand::class, $brand);
                break;
        }

        return $return;
    }

    private function collectParams(
        Request $request,
        ?array  $slugObject = null
    ): \stdClass
    {
        $requestParams = ($request->isMethod("POST")) ? $request->request : $request->query;
        $getValue = function ($key) use ($requestParams) {
            if (!$requestParams->has($key)) {
                return null;
            }
            $value = $requestParams->get($key);
            if ($value == "undefined") {
                return null;
            }

            return $value;
        };
        $search = new \stdClass();

        $sort = $request->attributes->get('sort');
        $order = match ($sort) {
            2 => ["column" => 1, "dir" => "DESC"],
            3 => ["column" => 2, "dir" => "ASC"],
            4 => ["column" => 2, "dir" => "DESC"],
            5 => ["column" => 3, "dir" => "DESC"],
            6 => ["column" => 3, "dir" => "ASC"],
            default => ["column" => 0, "dir" => "DESC"],
        };
        $search->ordr = $order;
        $search->specs = $requestParams->get('specs');
        $search->hasStock = $requestParams->get('inStock');
        $search->minPrice = $getValue('minPrice');
        $search->maxPrice = $getValue('maxPrice');
        $search->priceFrom = $getValue('priceFrom') ? $getValue('priceFrom') : 1;
        $search->priceTo = $getValue('priceTo');
        $search->string = $getValue('str');
        $search->offer = $requestParams->get('offer');
        $search->categories = $requestParams->get('categories');
        $search->brands = $requestParams->get('brands');
        $search->brand = $requestParams->get('brand');

        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }
        $routeName = $request->attributes->get('_route');

        switch ($routeName) {
            case "fe_product_filter_sale":
                $search->offer = true;
                break;
        }

        if (is_array($slugObject) and $slugObject['class'] == Collection::class) {
            $search->collection = $slugObject['entity']->getId();
        } elseif (is_array($slugObject) and $slugObject['class'] == Occasion::class) {
            $search->occasion = $slugObject['entity']->getId();
        } elseif (is_array($slugObject) and $slugObject['class'] == Category::class) {
            $search->category = $slugObject['entity']->getId();
        } elseif (is_array($slugObject) and $slugObject['class'] == Brand::class) {
            $search->brand = $slugObject['entity']->getId();
        }

        return $search;
    }


}
