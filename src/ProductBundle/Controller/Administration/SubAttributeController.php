<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\SubAttribute;
use App\ProductBundle\Form\SubAttributeType;
use App\ProductBundle\Repository\ProductHasAttributeRepository;
use App\ProductBundle\Service\SubAttributeService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Attribute controller.
 *
 * @Route("/sub-attribute")
 */
class SubAttributeController extends AbstractController
{

    /**
     * @Route("/new/{id}", name="sub_attribute_new", methods={"POST"})
     */
    public function new(Request $request, SubAttributeService $subAttributeService, Attribute $attribute): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $subAttribute = new SubAttribute();
        $subAttribute->setAttribute($attribute);
        $form = $this->createForm(SubAttributeType::class, $subAttribute);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($subAttribute);
            $this->em()->flush();
            $subAttributeForm = $this->createForm(SubAttributeType::class, new SubAttribute(), [
                "action" => $this->generateUrl("sub_attribute_new", ["id" => $attribute->getId()]),
            ]);

            $subAttributeData = $subAttributeService->getEditSubAttributesForm($attribute, 1);
            $html = $this->renderView("product/admin/attribute/subAttribute/_edit_sub_attributes_form.html.twig", [
                'edit_sub_attribute_form' => $subAttributeData->form->createView(),
                'paginator' => $subAttributeData->paginator->getPagination(),
                'new_sub_attribute_form' => $subAttributeForm->createView(),
            ]);

            return $this->json(["error" => 0, "message" => "Successfully saved", "html" => $html]);
        }

        return $this->json(["error" => 1, "message" => "Error"]);
    }

    /**
     * @Route("/{id}/edit/{page}", requirements={"id"="\d+", "page"="\d+"}, name="sub_attribute_edit", methods={"POST"})
     */
    public function edit(
        Request $request,
        SubAttributeService $subAttributeService,
        Attribute $attribute,
        $page
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $subAttributeData = $subAttributeService->getEditSubAttributesForm($attribute, $page);
        $form = $subAttributeData->form;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('attribute_edit', ['id' => $attribute->getId(), "page" => $page]);
        }

        return $this->redirectToRoute('attribute_edit', ['id' => $attribute->getId(), "page" => $page]);
    }

    /**
     * @Route("/{id}/delete", name="sub_attribute_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        UserService $userService,
        ProductHasAttributeRepository $productHasAttributeRepository,
        SubAttribute $subAttribute
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);


        $noOfUsage = $productHasAttributeRepository->countBySubAttribute($subAttribute);
        if ($noOfUsage > 0) {
            return $this->json([
                "error" => 1,
                "message" => "You can't delete this item because it is already used in products",
            ]);
        }


        $userName = $userService->getUserName();
        $subAttribute->setDeleted(new \DateTime());
        $subAttribute->setDeletedBy($userName);
        $this->em()->persist($subAttribute);
        $this->em()->flush();

        return $this->json(["error" => 0, "message" => "Successfully deleted"]);
    }
}
