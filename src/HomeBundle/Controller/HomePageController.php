<?php

namespace App\HomeBundle\Controller;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Enum\BannerPlacementEnum;
use App\CMSBundle\Repository\BlogRepository;
use App\CMSBundle\Service\BannerService;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\CollectionRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\ProductSearchService;
use App\UserBundle\Entity\User;
use PN\SeoBundle\Repository\SeoPageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * HomePage controller.
 *
 * @Route("")
 */
class HomePageController extends AbstractController
{

    /**
     * @Route("", name="fe_home", methods={"GET"})
     */
    public function index(
        SeoPageRepository $seoPageRepository,
        BannerService     $bannerService,
    ): Response
    {
        $seoPage = $seoPageRepository->findOneByType("home-page");
        $banners = $bannerService->getBanners(BannerPlacementEnum::HOME_PAGE_SLIDER, 6);
        return $this->render('home/homePage/index.html.twig', [
            "seoPage" => $seoPage,
            "banners" => $banners,
        ]);
    }

    /**
     * @Route("/remove-image", name="fe_remove", methods={"GET"})
     */
    public function removeImage()
    {
        $this->createAccessDeniedException();
        $products = $this->em()->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $images = $product->getPost()->getImages();
            foreach ($images as $image) {
                $this->em()->remove($image);
            }
            $this->em()->flush();
        }
        $categories = $this->em()->getRepository(Category::class)->findAll();
        foreach ($categories as $category) {
            $image = $category->getImage();
            if ($image != null) {
                $category->setImage(null);
                $this->em()->remove($image);
            }
            $images = $category->getPost()->getImages();
            foreach ($images as $image) {
                $this->em()->remove($image);
            }
            $this->em()->flush();
        }
        $images = $this->em()->getRepository(Image::class)->findAll();
        foreach ($images as $image) {
            if (str_contains($image->getBasePath(), "/product/")) {
                $this->em()->remove($image);
            }
            $this->em()->flush();
        }
    }


    /**
     * @Route("/first-banners-section", name="fe_home_first_banners_section_ajax", methods={"GET"})
     */
    public function firstBannersSection(BannerService $bannerService): Response
    {
        $banners = $bannerService->getBanners(BannerPlacementEnum::HOME_PAGE_TOP_2_ITEM, 2);

        return $this->render('home/homePage/firstBannersSection.html.twig', [
            "banners" => $banners,
        ]);
    }

    /**
     * @Route("/second-banners-section", name="fe_home_second_banners_section_ajax", methods={"GET"})
     */
    public function secondBannersSection(BannerService $bannerService): Response
    {
        $threeBanners = $bannerService->getBanners(BannerPlacementEnum::HOME_PAGE_MIDDLE_3_ITEMS, 3);
        $twoBanners = $bannerService->getBanners(BannerPlacementEnum::HOME_PAGE_MIDDLE_2_ITEMS, 2);

        return $this->render('home/homePage/secondBannersSection.html.twig', [
            "threeBanners" => $threeBanners,
            "twoBanners" => $twoBanners,
        ]);
    }

    /**
     * @Route("/new-arrivals", name="fe_home_new_arrivals_section_ajax", methods={"GET"})
     */
    public function newArrivalsSection(
        TranslatorInterface     $translator,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository
    ): Response
    {
        $products = $this->getNewArrivalsProducts($productSearchRepository);

        $return = [
            "title" => [
                "title" => $translator->trans("new_arrivals_txt"),
                "subTitle" => null,
                "icon" => null,
                "style" => 3,
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
     * @Route("/recommended-for-you-section", name="fe_home_recommended_for_you_section_ajax", methods={"GET"})
     */
    public function recommendedForYouSection(
        TranslatorInterface     $translator,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository
    ): Response
    {
        $products = $this->getRecommendedForYouProducts($productSearchRepository);

        $return = [
            "title" => [
                "title" => $translator->trans("recommended_for_you_txt"),
                "subTitle" => null,
                "icon" => null,
                "style" => 3,
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
     * @Route("/third-banners-section", name="fe_home_third_banners_section_ajax", methods={"GET"})
     */
    public function thirdBannersSection(BannerService $bannerService): Response
    {
        $twoBanners = $bannerService->getBanners(BannerPlacementEnum::HOME_PAGE_UNDER_MIDDLE_2_ITEMS, 2);
        $oneBanner = $bannerService->getOneBanner(BannerPlacementEnum::HOME_PAGE_UNDER_MIDDLE_FULL_WIDTH);


        return $this->render('home/homePage/thirdBannersSection.html.twig', [
            "twoBanners" => $twoBanners,
            "oneBanner" => $oneBanner,
        ]);
    }

    /**
     * @Route("/best-seller-section", name="fe_home_best_seller_section_ajax", methods={"GET"})
     */
    public function bestSellerSection(
        TranslatorInterface     $translator,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository
    ): Response
    {
        $products = $this->getBestSellerProducts($productSearchRepository);

        $return = [
            "title" => [
                "title" => $translator->trans("best_seller_txt"),
                "style" => 3,
                "icon" => null,
                "subTitle" => $translator->trans("products_txt"),
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
     * @Route("/collections-section", name="fe_home_collection_section_ajax", methods={"GET"})
     */
    public function collectionsSection(CollectionRepository $collectionRepository): Response
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = true;
        $search->featured = true;
        $search->hasProducts = true;

        $collections = $collectionRepository->filter($search, false, 0, 6);

        return $this->render('home/homePage/collectionsSection.html.twig', [
            "collections" => $collections,
        ]);
    }

    /**
     * @Route("/blogs-section", name="fe_home_blog_section_ajax", methods={"GET"})
     */
    public function blogsSection(BlogRepository $blogRepository): Response
    {
        $search = new \stdClass;
        $search->ordr = ["column" => 1, "dir" => "ASC"];
        $search->deleted = 0;
        $search->publish = 1;
        $search->featured = 1;

        $blogs = $blogRepository->filter($search, false, 0, 4);

        return $this->render('home/homePage/blogsSection.html.twig', [
            "blogs" => $blogs,
        ]);
    }

    /**
     * @Route("/fourth-banners-section", name="fe_home_fourth_banners_section_ajax", methods={"GET"})
     */
    public function fourthBannersSection(BannerService $bannerService): Response
    {
        $oneBanner = $bannerService->getOneBanner(BannerPlacementEnum::HOME_PAGE_BOTTOM_FULL_WIDTH);

        return $this->render('home/homePage/fourthBannersSection.html.twig', [
            "oneBanner" => $oneBanner,
        ]);
    }

    /**
     * @Route("/featured-category-section", name="fe_home_featured_category_section_ajax", methods={"GET"})
     */
    public function featuredCategorySection(
        TranslatorInterface     $translator,
        ProductSearchRepository $productSearchRepository,
        CategoryRepository      $categoryRepository,
        ProductSearchService    $productSearchService
    ): Response
    {
        $categories = $this->getFeaturedCategories($productSearchRepository, $categoryRepository);

        $return = [];
        foreach ($categories as $category) {

            $object = [
                "title" => [
                    "title" => $category->getTitle(),
                    "subTitle" => $translator->trans("products_txt"),
                    "icon" => null,
                    "style" => 3,
                    "actionBtn" => [
                        "text" => $translator->trans("view_all_txt"),
                        "link" => $this->generateUrl("fe_product_filter_category",
                            ["slug" => $category->getSeo()->getSlug()]),
                    ],
                ],
                "products" => [],
            ];
            foreach ($category->virtualProducts as $product) {
                $object["products"][] = $productSearchService->convertEntityToObject($product);
            }

            $return[] = $object;
        }


        return $this->json($return);
    }

    //====================================================================PRIVATE METHODS====================================================================

    private function getRecommendedForYouProducts(ProductSearchRepository $productSearchRepository): array
    {
        $search = new \stdClass();
        $search->ordr = ["column" => 4, "dir" => "DESC"];;
        //        $search->offer = true;
        $search->featured = true;
        $search->hasStock = true;
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }

        return $productSearchRepository->filter($search, false, 0, 12);
    }

    private function getNewArrivalsProducts(ProductSearchRepository $productSearchRepository): array
    {
        $search = new \stdClass();
        $search->ordr = ["column" => 4, "dir" => "DESC"];;
        //        $search->offer = true;
//        $search->featured = true;
        $search->newArrival = true;
        $search->hasStock = true;
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }

        return $productSearchRepository->filter($search, false, 0, 12);
    }

    // not optimized
    private function getFeaturedCategories(
        ProductSearchRepository $productSearchRepository,
        CategoryRepository      $categoryRepository
    ): array
    {
        $featuredCategories = $categoryRepository->findBy([
            //            'parent' => null,
            'deleted' => null,
            'featured' => true,
            'publish' => true,
        ], null, 3);

        $categoryProducts = [];
        foreach ($featuredCategories as $featuredCategory) {
            $products = $this->getFeaturedProductsByCategory(
                $productSearchRepository,
                $featuredCategory
            );
            if (count($products) > 0) {
                $featuredCategory->virtualProducts = $products;
                $categoryProducts[] = $featuredCategory;
            }

        }

        return $categoryProducts;
    }

    private function getFeaturedProductsByCategory(ProductSearchRepository $productSearchRepository, Category $category): array
    {
        $search = new \stdClass();
        $search->ordr = ["column" => 4, "dir" => "DESC"];;
//        $search->featured = 1;
        $search->hasStock = true;
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }
        $search->categories = explode(',', $category->getConcatIds());

        return $productSearchRepository->filter($search, false, 0, 12);
    }

    private function getBestSellerProducts(ProductSearchRepository $productSearchRepository): array
    {
        $search = new \stdClass();
        $search->bestSeller = true;
        $search->hasStock = true;
        $search->ordr = ["column" => 4, "dir" => "DESC"];;
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }

        return $productSearchRepository->filter($search, false, 0, 12);
    }


}
