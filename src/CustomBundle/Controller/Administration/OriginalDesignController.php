<?php

namespace App\CustomBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\CustomBundle\Entity\OriginalDesign;
use App\CustomBundle\Form\OriginalDesignType;
use App\CustomBundle\Repository\OriginalDesignRepository;
use App\UserBundle\Model\UserInterface;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("original-design")
 */
class OriginalDesignController extends AbstractController
{

    /**
     * @Route("/", name="original_design_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('custom/admin/originalDesign/index.html.twig');
    }

    /**
     * @Route("/new", name="original_design_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $originalDesign = new OriginalDesign();
        $form = $this->createForm(OriginalDesignType::class, $originalDesign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($originalDesign);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('post_set_images', ['id' => $originalDesign->getPost()->getId(),]
            );
        }

        return $this->render('custom/admin/originalDesign/new.html.twig', [
            'originalDesign' => $originalDesign,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="original_design_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, OriginalDesign $originalDesign): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $form = $this->createForm(OriginalDesignType::class, $originalDesign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->flush();
            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('original_design_index');
        }

        return $this->render('custom/admin/originalDesign/edit.html.twig', [
            'originalDesign' => $originalDesign,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="original_design_delete", methods={"DELETE"})
     */
    public function delete(OriginalDesign $originalDesign, UserService $userService): Response
    {
        $this->denyAccessUnlessGranted(UserInterface::ROLE_ADMIN);

        $userName = $userService->getUserName();
        $originalDesign->setDeletedBy($userName);
        $originalDesign->setDeleted(new \DateTime(date('Y-m-d H:i:s')));
        $this->em()->persist($originalDesign);
        $this->em()->flush();
        $this->addFlash("success", "Deleted Successfully");

        return $this->redirectToRoute('original_design_index');
    }

    /**
     * @Route("/data/table", defaults={"_format": "json"}, name="original_design_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, OriginalDesignRepository $originalDesignRepository): Response
    {
        $srch = $request->query->all("search");
        $start = $request->query->getInt("start");
        $length = $request->query->getInt("length");
        $ordr = $request->query->all("order");

        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->deleted = 0;

        $count = $originalDesignRepository->filter($search, true);
        $originalDesigns = $originalDesignRepository->filter($search, false, $start, $length);

        return $this->render("custom/admin/originalDesign/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "originalDesigns" => $originalDesigns,
        ]);
    }

}
