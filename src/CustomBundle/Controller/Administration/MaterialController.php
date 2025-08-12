<?php

namespace App\CustomBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CustomBundle\Entity\Material;
use App\CustomBundle\Entity\MaterialCategory;
use App\CustomBundle\Form\MaterialType;
use App\CustomBundle\Repository\MaterialRepository;
use App\UserBundle\Model\UserInterface;
use PN\MediaBundle\Entity\Image;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("material")
 */
class MaterialController extends AbstractController
{

    /**
     * @Route("/{id}", name="material_index", methods={"GET"})
     */
    public function index(MaterialCategory $category): Response
    {
        return $this->render('custom/admin/material/index.html.twig', [
            "category" => $category,
        ]);
    }

    /**
     * @Route("/new/{id}", name="material_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UploadImageService $uploadImageService, MaterialCategory $category): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $material = new Material();
        $material->setCategory($category);
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($material);
            $this->em()->flush();

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $material);
            if ($uploadImage === false) {
                return $this->redirectToRoute('material_edit', ["id" => $material->getId()]);
            }

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('material_index', ["id" => $material->getCategory()->getId()]);
        }

        return $this->render('custom/admin/material/new.html.twig', [
            'material' => $material,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="material_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UploadImageService $uploadImageService, Material $material): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($material);
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            $uploadImage = $this->uploadImage($request, $uploadImageService, $form, $material);
            if ($uploadImage === false) {
                return $this->redirectToRoute('material_edit', ["id" => $material->getId()]);
            }

            return $this->redirectToRoute('material_index', ["id" => $material->getCategory()->getId()]);
        }

        return $this->render('custom/admin/material/edit.html.twig', [
            'material' => $material,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="material_delete", methods={"DELETE"})
     */
    public function delete(Material $material, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $userName = $userService->getUserName();
        $material->setDeletedBy($userName);
        $material->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($material);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('material_index', ["id" => $material->getCategory()->getId()]);
    }

    /**
     * @Route("/data/table/{id}", defaults={"_format": "json"}, name="material_datatable", methods={"GET"})
     */
    public function dataTable(
        Request $request,
        MaterialCategory $category,
        MaterialRepository $materialRepository
    ): Response {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;
        $search->category = $category->getId();

        $count = $materialRepository->filter($search, true);
        $materials = $materialRepository->filter($search, false, $start, $length);

        return $this->render("custom/admin/material/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "materials" => $materials,
        ]);
    }

    private function uploadImage(
        Request $request,
        UploadImageService $uploadImageService,
        FormInterface $form,
        Material $entity
    ): bool|string|Image {
        $file = $form->get("image")->get("file")->getData();
        if (!$file instanceof UploadedFile) {
            return false;
        }

        return $uploadImageService->uploadSingleImage($entity->getPost(), $file, 5, $request);
    }
}
