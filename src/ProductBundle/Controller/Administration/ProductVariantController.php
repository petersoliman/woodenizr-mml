<?php

namespace App\ProductBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\ProductPriceTypeEnum;
use App\BaseBundle\SystemConfiguration;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Form\ProductVariantOptionType;
use App\ProductBundle\Form\ProductVariantType;
use App\ProductBundle\Repository\ProductVariantRepository;
use App\ProductBundle\Service\ProductVariantOptionService;
use App\ProductBundle\Service\ProductVariantService;
use PN\ServiceBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/variant")
 */
class ProductVariantController extends AbstractController
{

    /**
     * @Route("/{id}", name="product_variant_index", methods={"GET"})
     */
    public function index(Request $request, Product $product): Response
    {
        $this->firewall($product);

        return $this->render("product/admin/productVariant/index.html.twig", [
            'product' => $product,
        ]);
    }

    /**
     * Displays a form to create a new Attribute entity.
     *
     * @Route("/new/{id}", name="product_variant_new", methods={"GET", "POST"})
     */
    public function new(Request $request, Product $product): Response
    {
        $this->firewall($product);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $form = $this->createForm(ProductVariantType::class, $variant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em()->persist($variant);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_variant_edit', ["id" => $variant->getId()]);
        }


        return $this->render("product/admin/productVariant/new.html.twig", [
            'variant' => $variant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit/{page}", requirements={"id"="\d+", "page"="\d+"}, name="product_variant_edit", methods={"GET", "POST"})
     */
    public function edit(
        Request                     $request,
        ProductVariantService       $productVariantService,
        ProductVariantOptionService $productVariantOptionService,
        ProductVariant              $variant,
                                    $page = 1
    ): Response
    {
        $this->firewall($variant->getProduct());

        $form = $this->createForm(ProductVariantType::class, $variant, [
            "disableTypeField" => $variant->getOptions()->count() > 0,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em()->persist($variant);
            $this->em()->flush();

            $this->addFlash('success', 'Successfully saved');

            return $this->redirectToRoute('product_variant_edit', ['id' => $variant->getId()]);
        }

        $productVariantOption = new ProductVariantOption();
        $productVariantOption->setVariant($variant);
        $optionForm = $this->createForm(ProductVariantOptionType::class, $productVariantOption, [
            "action" => $this->generateUrl("product_variant_option_new", ["id" => $variant->getId()]),
        ]);

        $optionData = $productVariantService->getEditOptionsForm($variant, $page);

        return $this->render("product/admin/productVariant/edit.html.twig", [
            'variant' => $variant,
            'form' => $form->createView(),
            'new_option_form' => $optionForm->createView(),
            'edit_option_form' => $optionData->form->createView(),
            'paginator' => $optionData->paginator->getPagination(),
        ]);
    }


    /**
     * @Route("/{id}", name="product_variant_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserService $userService, ProductVariantOptionService $productVariantOptionService, ProductVariant $variant): Response
    {
        $this->firewall($variant->getProduct());
        $userName = $userService->getUserName();

        foreach ($variant->getOptions() as $option) {
            $productVariantOptionService->deleteVariantOption($option);
            $option->setDeleted(new \DateTime());
            $option->setDeletedBy($userName);
            $this->em()->persist($option);
        }


        $variant->setDeleted(new \DateTime());
        $variant->setDeletedBy($userName);
        $this->em()->persist($variant);
        $this->em()->flush();

        $this->addFlash('success', 'Successfully deleted');

        return $this->redirectToRoute('product_variant_index', ["id" => $variant->getProduct()->getId()]);
    }

    /**
     * @Route("/data/table/{id}", defaults={"_format": "json"}, name="product_variant_datatable", methods={"GET"})
     */
    public function dataTable(
        Request                  $request,
        ProductVariantRepository $productVariantRepository,
        Product                  $product
    ): Response
    {
        $this->firewall($product);
        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];
        $search->product = $product->getId();
        $search->deleted = 0;

        $count = $productVariantRepository->filter($search, true);
        $variants = $productVariantRepository->filter($search, false, $start, $length);

        return $this->render("product/admin/productVariant/datatable.json.twig", [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "variants" => $variants,
        ]);
    }

    private function firewall(Product $product)
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        if (SystemConfiguration::PRODUCT_PRICE_TYPE != ProductPriceTypeEnum::VARIANTS) {
            throw $this->createNotFoundException();
        } elseif (SystemConfiguration::PRODUCT_PRICE_TYPE == ProductPriceTypeEnum::VARIANTS and !$product->isEnableVariants()) {
            throw $this->createNotFoundException();
        }
    }

}
