<?php

namespace App\CustomBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CustomBundle\Entity\MaterialCategory;
use App\CustomBundle\Form\MaterialCategoryType;
use App\CustomBundle\Repository\MaterialCategoryRepository;
use App\UserBundle\Model\UserInterface;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("material-category")
 */
class MaterialCategoryController extends AbstractController
{

    /**
     * @Route("/", name="material_category_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('custom/admin/materialCategory/index.html.twig');
    }

    /**
     * @Route("/new", name="material_category_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $materialCategory = new MaterialCategory();
        $form = $this->createForm(MaterialCategoryType::class, $materialCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($materialCategory);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('material_category_index');
        }

        return $this->render('custom/admin/materialCategory/new.html.twig', [
            'materialCategory' => $materialCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="material_category_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, MaterialCategory $materialCategory): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $form = $this->createForm(MaterialCategoryType::class, $materialCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('material_category_index');
        }

        return $this->render('custom/admin/materialCategory/edit.html.twig', [
            'materialCategory' => $materialCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="material_category_delete", methods={"DELETE"})
     */
    public function delete(MaterialCategory $materialCategory, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $userName = $userService->getUserName();
        $materialCategory->setDeletedBy($userName);
        $materialCategory->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($materialCategory);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('material_category_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="material_category_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, MaterialCategoryRepository $materialCategoryRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $materialCategoryRepository->filter($search, true);
        $materialCategories = $materialCategoryRepository->filter($search, false, $start, $length);

        return $this->render("custom/admin/materialCategory/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "materialCategories" => $materialCategories,
        ]);
    }

}
