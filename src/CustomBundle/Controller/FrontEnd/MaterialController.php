<?php

namespace App\CustomBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\CustomBundle\Repository\MaterialCategoryRepository;
use App\CustomBundle\Repository\MaterialRepository;
use PN\SeoBundle\Repository\SeoPageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("material")
 */
class MaterialController extends AbstractController
{

    /**
     * @Route("", name="fe_material_index" ,methods={"GET"})
     */
    public function index(
        Request                    $request,
        SeoPageRepository          $seoPageRepository,
        MaterialCategoryRepository $materialCategoryRepository
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
            $materialCategories = $materialCategoryRepository->filter($search);

            return $this->render("custom/frontEnd/material/_content.html.twig", [
                'materialCategories' => $materialCategories,
            ]);
        }

        return $this->render("custom/frontEnd/material/index.html.twig", [
            'search' => $search,
            'seoPage' => $seoPageRepository->findOneByType("materials"),
        ]);
    }


}
