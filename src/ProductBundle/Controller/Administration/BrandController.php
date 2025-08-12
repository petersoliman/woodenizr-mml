<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CMSBundle\Lib\Paginator;
use App\ProductBundle\Entity\Brand;
use App\ProductBundle\Form\BrandType;
use App\ProductBundle\Repository\BrandRepository;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Brand controller.
 *
 * @Route("/brand")
 */
class BrandController extends AbstractController
{

    /**
     * Lists all Brand entities.
     *
     * @Route("/", name="brand_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render("product/admin/brand/index.html.twig");
    }

    /**
     * Displays a form to create a new Brand entity.
     *
     * @Route("/new", name="brand_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserService $userService): Response
    {
        $brand = new Brand();
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($brand);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');
            return $this->redirectToRoute('brand_index');
        }

        return $this->render("product/admin/brand/new.html.twig", [
            'brand' => $brand,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="brand_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, BrandRepository $brandRepository, Brand $brand): Response
    {
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($brand);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');
            return $this->redirectToRoute('brand_index');
        }

        return $this->render("product/admin/brand/edit.html.twig", [
            'brand' => $brand,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="brand_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Brand $brand): Response
    {
        $brand->setDeleted(new \DateTime());
        $brand->setDeletedBy($userService->getUserName());
        $this->em()->persist($brand);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully deleted');

        return $this->redirectToRoute('brand_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="brand_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, BrandRepository $brandRepository): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $brandRepository->filter($search, true);
        $brands = $brandRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/brand/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "brands" => $brands,
        ]);
    }

    /**
     * search product ajax.
     *
     * @Route("/related/product/ajax", name="brand_select_ajax", methods={"GET"})
     */
    public function searchSelect2(Request $request, BrandRepository $brandRepository): Response
    {
        $page = ($request->query->has('page')) ? $request->get('page') : 1;

        $search = new \stdClass;
        $search->admin = true;
        $search->deleted = 0;
        $search->string = $request->get('q');

        $count = $brandRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 10);
        $entities = $brandRepository->filter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());

        $paginationFlag = false;
        if (isset($paginator->getPagination()['last']) and $paginator->getPagination()['last'] != $page) {
            $paginationFlag = true;
        }

        $returnArray = [
            'results' => [],
            'pagination' => $paginationFlag,
        ];

        foreach ($entities as $entity) {
            $title = $entity->getTitle();
            if (!$entity->isPublish()) {
                $title .= " (Unpublished)";
            }
            $returnArray['results'][] = [
                'id' => $entity->getId(),
                'text' => $title,
            ];
        }

        return $this->json($returnArray);
    }
}
