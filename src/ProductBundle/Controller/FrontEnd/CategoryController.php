<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Lib\Paginator;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Service\CategoryService;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Blog controller.
 *
 * @Route("category")
 */
class CategoryController extends AbstractController
{

    /**
     * @Route("/{slug}/{page}", requirements={"page": "\d+"}, defaults={"slug": "null"}, name="fe_category_index", methods={"GET"})
     */
    public function index(
        Request            $request,
        SeoService         $seoService,
        SeoPageRepository  $seoPageRepository,
        CategoryService    $categoryService,
        CategoryRepository $categoryRepository,
        int                $page = 1,
        string             $slug = null
    ): Response
    {

        $search = new \stdClass;
        $search->deleted = 0;
        $search->publish = 1;
        $search->ordr = ["column" => 1, "dir" => "DESC"]; // Use inverseTarteb column with DESC for proper priority sorting
        $search->string = $request->get("q");
        $search->parent = "";

        $category = $seoPage = null;
        if ($slug !== null) {
            $seoPage = $category = $seoService->getSlug($request, $slug, new Category());
            if (!$category) {
                throw $this->createNotFoundException();
            }

            if ($category->getChildren()->count() < 1) {
                return $this->redirectToRoute("fe_product_filter_category", ["slug" => $category->getSeo()->getSlug()]);
            }

            $search->parent = $category->getId();
        }

        if ($request->query->has("content")) {
            $request->query->remove("content");

            $count = $categoryRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 12);
            $categories = ($count > 0) ? $categoryRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];
            return $this->render("product/frontEnd/category/_content.html.twig", [
                'categories' => $categories,
                'parentCategory' => $category,
                "paginator" => $paginator->getPagination(),
            ]);
        }


        $categoryParents = $categoryService->parentsByChild($category);

        if (!$category instanceof Category) {
            $seoPage = $seoPageRepository->findOneByType("categories");
        }

        return $this->render("product/frontEnd/category/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPage,
            'parentCategory' => $category,
            'categoryParents' => $categoryParents,
        ]);
    }


}
