<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use App\ProductBundle\Form\ProductVariantOptionType;
use App\ProductBundle\Repository\ProductHasAttributeRepository;
use App\ProductBundle\Service\ProductVariantOptionService;
use App\ProductBundle\Service\ProductVariantService;
use PN\MediaBundle\Entity\Image;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/variant-option")
 */
class ProductVariantOptionController extends AbstractController
{

    /**
     * @Route("/new/{id}", name="product_variant_option_new", methods={"POST"})
     */
    public function new(
        Request $request,
        UploadImageService $uploadImageService,
        ProductVariantService $productVariantService,
        ProductVariantOptionService $productVariantOptionService,
        ProductVariant $variant
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $option = new ProductVariantOption();
        $option->setVariant($variant);
        $form = $this->createForm(ProductVariantOptionType::class, $option);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $message = "Successfully saved";
            $this->em()->persist($option);
            $this->em()->flush();

            $productVariantOptionService->addNewVariantOptionCombos($option);

            $uploadImage = $this->uploadImage($uploadImageService, $form, $option);
            if (is_string($uploadImage)) {
                $message = $uploadImage;
            }

            $productVariantOption = new ProductVariantOption();
            $productVariantOption->setVariant($variant);
            $optionForm = $this->createForm(ProductVariantOptionType::class, $productVariantOption, [
                "action" => $this->generateUrl("product_variant_option_new", ["id" => $variant->getId()]),
            ]);

            $optionData = $productVariantService->getEditOptionsForm($variant, 1);
            $html = $this->renderView("product/admin/productVariant/option/_edit_options_form.html.twig", [
                'variant' => $variant,
                'edit_option_form' => $optionData->form->createView(),
                'paginator' => $optionData->paginator->getPagination(),
                'new_option_form' => $optionForm->createView(),
            ]);

            return $this->json(["error" => false, "message" => $message, "html" => $html]);
        }

        return $this->json(["error" => true, "message" => "Error"]);
    }

    /**
     * @Route("/{id}/edit/{page}", requirements={"id"="\d+", "page"="\d+"}, name="product_variant_option_edit", methods={"POST"})
     */
    public function edit(
        Request $request,
        UploadImageService $uploadImageService,
        ProductVariantService $productVariantService,
        ProductVariantOptionService $productVariantOptionService,
        ProductVariant $variant,
        $page
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $optionData = $productVariantService->getEditOptionsForm($variant, $page);
        $form = $optionData->form;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($variant->getType() == ProductVariantTypeEnum::IMAGE) {
                foreach ($form->get("options")->all() as $formItem) {
                    $this->uploadImage($uploadImageService, $formItem, $formItem->getData(), $request);
                }
            }
            $this->em()->persist($variant);
            $this->em()->flush();
            $this->em()->refresh($variant);

            $productVariantOptionService->updateProductPrice($variant);


            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_variant_edit', ['id' => $variant->getId(), "page" => $page]);
        }

        return $this->redirectToRoute('product_variant_edit', ['id' => $variant->getId(), "page" => $page]);
    }

    /**
     * @Route("/{id}/delete", name="product_variant_option_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        UserService $userService,
        ProductVariantOptionService $productVariantOptionService,
        ProductVariantOption $option
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);
        $productVariantOptionService->deleteVariantOption($option);

        $userName = $userService->getUserName();
        $option->setDeleted(new \DateTime());
        $option->setDeletedBy($userName);
        $this->em()->persist($option);
        $this->em()->flush();

        return $this->json(["error" => 0, "message" => "Successfully deleted"]);
    }

    private function uploadImage(
        UploadImageService $uploadImageService,
        FormInterface $form,
        ProductVariantOption $entity,
        Request $request = null
    ): bool|string|Image {
        if (!$form->has("image")) {
            return true;
        }
        $file = $form->get("image")->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }

        return $uploadImageService->uploadSingleImage($entity, $file, 102, $request);
    }


}
