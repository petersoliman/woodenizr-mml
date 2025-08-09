<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Lib\Paginator;
use App\ProductBundle\Repository\CollectionRepository;
use PN\SeoBundle\Repository\SeoPageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("collection")
 */
class CollectionController extends AbstractController
{

    /**
     * @Route("/{page}", requirements={"page": "\d+"}, name="fe_collection_index" ,methods={"GET"})
     */
    public function index(
        Request              $request,
        SeoPageRepository    $seoPageRepository,
        CollectionRepository $collectionRepository,
        int                  $page = 1
    ): Response
    {
        $search = new \stdClass;
        $search->deleted = 0;
        $search->publish = 1;
        $search->ordr = ["column" => 0, "dir" => "ASC"];


        if ($request->query->has("content")) {
            $request->query->remove("content");

            $count = $collectionRepository->filter($search, true);
            $paginator = new Paginator($count, $page, 12);
            $collections = ($count > 0) ? $collectionRepository->filter($search, false, $paginator->getLimitStart(),
                $paginator->getPageLimit()) : [];
            return $this->render("product/frontEnd/collection/_content.html.twig", [
                'seoPage' => $seoPageRepository->findOneByType("collections"),
                'collections' => $collections,
                "paginator" => $paginator->getPagination(),
            ]);
        }

        return $this->render("product/frontEnd/collection/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPageRepository->findOneByType("collections"),
        ]);
    }


}
