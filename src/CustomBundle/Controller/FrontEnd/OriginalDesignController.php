<?php

namespace App\CustomBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Lib\Paginator;
use App\CustomBundle\Entity\OriginalDesign;
use App\CustomBundle\Repository\OriginalDesignRepository;
use PN\SeoBundle\Repository\SeoPageRepository;
use PN\SeoBundle\Service\SeoService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("original-design")
 */
class OriginalDesignController extends AbstractController
{

    /**
     * @Route("/{page}", requirements={"page": "\d+"}, name="fe_original_design_index" ,methods={"GET"})
     */
    public function index(
        Request                  $request,
        SeoPageRepository        $seoPageRepository,
        OriginalDesignRepository $originalDesignRepository,
        int                      $page = 1
    ): Response
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->publish = 1;
        $search->ordr = ["column" => 0, "dir" => "ASC"];


        if ($request->query->has("content")) {
            $request->query->remove("content");

            $search = new \stdClass();
            $search->deleted = 0;
            $search->publish = 1;
            $search->ordr = ["column" => 1, "dir" => "ASC"];

            $count = $originalDesignRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 12);
            $originalDesigns = ($count > 0) ? $originalDesignRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];

            return $this->render("custom/frontEnd/originalDesign/_content.html.twig", [
                'seoPage' => $seoPageRepository->findOneByType("original-designs"),
                'originalDesigns' => $originalDesigns,
                "paginator" => $paginator->getPagination(),
            ]);
        }

        return $this->render("custom/frontEnd/originalDesign/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPageRepository->findOneByType("original-designs"),
        ]);
    }

    /**
     * @Route("/{slug}", name="fe_original_design_show" ,methods={"GET"})
     */
    public function show(
        Request           $request,
        SeoService        $seoService,
        SeoPageRepository $seoPageRepository,
        string            $slug
    ): Response
    {
        $originalDesign = $seoService->getSlug($request, $slug, new OriginalDesign());
        if (!$originalDesign) {
            throw $this->createNotFoundException();
        }
        if ($originalDesign instanceof RedirectResponse) {
            return $originalDesign;
        }

        return $this->render("custom/frontEnd/originalDesign/show.html.twig", [
            'originalDesign' => $originalDesign,
        ]);
    }

}
