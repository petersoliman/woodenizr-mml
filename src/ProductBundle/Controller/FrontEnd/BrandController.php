<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Lib\Paginator;
use App\ProductBundle\Repository\BrandRepository;
use PN\SeoBundle\Repository\SeoPageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("brand")
 */
class BrandController extends AbstractController
{

    /**
     * @Route("/{page}", requirements={"page": "\d+"}, name="fe_brand_index" ,methods={"GET"})
     */
    public function index(
        Request           $request,
        SeoPageRepository $seoPageRepository,
        BrandRepository   $brandRepository,
        int               $page = 1
    ): Response
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->publish = 1;
        $search->ordr = ["column" => 0, "dir" => "ASC"];


        if ($request->query->has("content")) {
            $request->query->remove("content");

            $count = $brandRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 12);
            $brands = ($count > 0) ? $brandRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];
            return $this->render("product/frontEnd/brand/_content.html.twig", [
                'seoPage' => $seoPageRepository->findOneByType("brands"),
                'brands' => $brands,
                "paginator" => $paginator->getPagination(),
            ]);
        }

        return $this->render("product/frontEnd/brand/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPageRepository->findOneByType("brands"),
        ]);
    }


}
