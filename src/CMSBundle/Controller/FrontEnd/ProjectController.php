<?php

namespace App\CMSBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Entity\Project;
use App\CMSBundle\Lib\Paginator;
use App\CMSBundle\Repository\ProjectRepository;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Repository\ProductSearchRepository;
use App\ProductBundle\Service\ProductSearchService;
use App\UserBundle\Entity\User;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("project")
 */
class ProjectController extends AbstractController
{

    /**
     * @Route("/{page}", requirements={"page": "\d+"}, name="fe_project_index" ,methods={"GET"})
     */
    public function index(
        Request           $request,
        SeoPageRepository $seoPageRepository,
        ProjectRepository $projectRepository,
        int               $page = 1
    ): Response
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->publish = 1;
        $search->ordr = ["column" => 0, "dir" => "ASC"];

        if ($request->query->has("content")) {
            $request->query->remove("content");

            $count = $projectRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 12);
            $projects = ($count > 0) ? $projectRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];
            return $this->render("cms/frontEnd/project/_content.html.twig", [
                'seoPage' => $seoPageRepository->findOneByType("projects"),
                'projects' => $projects,
                "paginator" => $paginator->getPagination(),
            ]);
        }

        return $this->render("cms/frontEnd/project/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPageRepository->findOneByType("projects"),
        ]);
    }

    /**
     * @Route("/{slug}", name="fe_project_show", methods={"GET"})
     */
    public function show(
        Request    $request,
        SeoService $seoService,
        string     $slug
    ): Response
    {
        $project = $seoService->getSlug($request, $slug, new Project());
        if ($project instanceof RedirectResponse) {
            return $project;
        }
        if (!$project) {
            throw $this->createNotFoundException();
        }

        return $this->render('cms/frontEnd/project/show.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/used-products-section/{slug}", name="fe_project_related_products_ajax", methods={"GET"})
     */
    public function relatedProductsSection(
        Request                 $request,
        TranslatorInterface     $translator,
        SeoService              $seoService,
        ProductSearchService    $productSearchService,
        ProductSearchRepository $productSearchRepository,
                                $slug
    ): Response
    {
        $project = $seoService->getSlug($request, $slug, new Project(), redirect: false);
        if (!$project) {
            return $this->json(["error" => true, "message" => $translator->trans("product_not_found_txt")]);
        }

        $products = $this->getRelatedProducts($productSearchRepository, $project);

        $return = [
            "title" => [
                "title" => "Used Products",
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

    private function getRelatedProducts(
        ProductSearchRepository $productSearchRepository,
        Project                 $project,
    ): array
    {
        $relatedProducts = $project->getRelatedProducts();
        $relatedProductIds = [];
        foreach ($relatedProducts as $relatedVendorProduct) {
            $relatedProductIds[] = $relatedVendorProduct->getId();
        }

        $search = new \stdClass();
        $search->ordr = 0;
        $search->ids = $relatedProductIds;
        if ($this->getUser() instanceof User) {
            $search->currentUserId = $this->getUser()->getId();
        }
        return $productSearchRepository->filter($search, false, 0, 21);
    }
}
