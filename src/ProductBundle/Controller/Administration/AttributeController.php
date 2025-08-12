<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\ProductHasAttribute;
use App\ProductBundle\Entity\SubAttribute;
use App\ProductBundle\Form\AttributeType;
use App\ProductBundle\Form\SubAttributeType;
use App\ProductBundle\Repository\AttributeRepository;
use App\ProductBundle\Repository\ProductHasAttributeRepository;
use App\ProductBundle\Service\CategoryService;
use App\ProductBundle\Service\SubAttributeService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Attribute controller.
 *
 * @Route("/attribute")
 */
class AttributeController extends AbstractController
{

    /**
     * @Route("/{id}", name="attribute_index", methods={"GET"})
     */
    public function index(Request $request, CategoryService $categoryService, Category $category): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);
        $categoryParents = $categoryService->parentsByChild($category);

        return $this->render("product/admin/attribute/index.html.twig", [
            'category' => $category,
            'categoryParents' => $categoryParents,
        ]);
    }

    /**
     * Displays a form to create a new Attribute entity.
     *
     * @Route("/new/{id}", name="attribute_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CategoryService $categoryService, Category $category): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $attribute = new Attribute();
        $attribute->setCategory($category);
        $form = $this->createForm(AttributeType::class, $attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($attribute);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');
            if (in_array($attribute->getType(), [Attribute::TYPE_DROPDOWN, Attribute::TYPE_CHECKBOX])) {
                return $this->redirectToRoute('attribute_edit', ["id" => $attribute->getId()]);
            }

            return $this->redirectToRoute('attribute_index', ["id" => $category->getId()]);
        }

        $categoryParents = $categoryService->parentsByChild($attribute->getCategory());

        return $this->render("product/admin/attribute/new.html.twig", [
            'attribute' => $attribute,
            'categoryParents' => $categoryParents,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit/{page}", requirements={"id"="\d+", "page"="\d+"}, name="attribute_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request $request,
        SubAttributeService $subAttributeService,
        CategoryService $categoryService,
        ProductHasAttributeRepository $productHasAttributeRepository,
        Attribute $attribute,
        $page = 1
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $isUsed = $productHasAttributeRepository->countByAttribute($attribute);
        $form = $this->createForm(AttributeType::class, $attribute, [
            "disableTypeField" => $isUsed > 0,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($attribute);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('attribute_edit', ['id' => $attribute->getId()]);
        }


        $categoryParents = $categoryService->parentsByChild($attribute->getCategory());
        $subAttributeForm = $this->createForm(SubAttributeType::class, new SubAttribute(), [
            "action" => $this->generateUrl("sub_attribute_new", ["id" => $attribute->getId()]),
        ]);

        $subAttributeData = $subAttributeService->getEditSubAttributesForm($attribute, $page);

        return $this->render("product/admin/attribute/edit.html.twig", [
            'attribute' => $attribute,
            'categoryParents' => $categoryParents,
            'form' => $form->createView(),
            'new_sub_attribute_form' => $subAttributeForm->createView(),
            'edit_sub_attribute_form' => $subAttributeData->form->createView(),
            'paginator' => $subAttributeData->paginator->getPagination(),
        ]);
    }


    /**
     * @Route("/{id}", name="attribute_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, Attribute $attribute): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $userName = $userService->getUserName();
        $attribute->setDeleted(new \DateTime());
        $attribute->setDeletedBy($userName);
        $this->em()->persist($attribute);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully deleted');

        return $this->redirectToRoute('attribute_index', ["id" => $attribute->getCategory()->getId()]);
    }

    /**
     * @Route("/data/table/{id}", defaults={"_format": "json"}, name="attribute_datatable", methods={"GET"})
     */
    public function dataTable(Request $request, AttributeRepository $attributeRepository, Category $category): Response
    {
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->category = $category->getId();
        $search->deleted = 0;

        $count = $attributeRepository->filter($search, true);
        $attributes = $attributeRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/attribute/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "attributes" => $attributes,
        ]);
    }

}
