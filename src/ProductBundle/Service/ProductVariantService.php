<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Enum\ProductVariantTypeEnum;
use App\ProductBundle\Form\ProductVariantOptionType;
use App\ProductBundle\Repository\ProductPriceHasVariantOptionRepository;
use App\ProductBundle\Repository\ProductVariantOptionRepository;
use App\ProductBundle\Repository\ProductVariantRepository;
use PN\ServiceBundle\Lib\Paginator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductVariantService
{

    private UrlGeneratorInterface $urlGenerator;
    private FormFactoryInterface $formFactory;
    private Packages $assetsManager;

    private ProductVariantOptionRepository $productVariantOptionRepository;
    private ProductPriceHasVariantOptionRepository $productPriceHasVariantOptionRepository;
    private ProductVariantRepository $productVariantRepository;

    public function __construct(
        UrlGeneratorInterface                  $urlGenerator,
        FormFactoryInterface                   $formFactory,
        Packages                               $assetsManager,
        ProductVariantOptionRepository         $productVariantOptionRepository,
        ProductPriceHasVariantOptionRepository $productPriceHasVariantOptionRepository,
        ProductVariantRepository               $productVariantRepository
    )
    {
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
        $this->assetsManager = $assetsManager;

        $this->productVariantOptionRepository = $productVariantOptionRepository;
        $this->productPriceHasVariantOptionRepository = $productPriceHasVariantOptionRepository;
        $this->productVariantRepository = $productVariantRepository;
    }

    public function getVariantsInObjectByProduct(Product $product): array
    {
        $search = new \stdClass;
        $search->ordr = ["column" => 1, "dir" => "DESC"];
        $search->product = $product->getId();
        $search->deleted = 0;
        $variants = $this->productVariantRepository->filter($search);

        $variantObjects = [];
        foreach ($variants as $variant) {
            $options = $this->productVariantOptionRepository->getOptionsHasPriceByVariant($variant);
            if (count($options) < 1) {
                continue;
            }


            $object = $variant->getObj();
            foreach ($options as $option) {
                $optionObj = $option->getObj();

                if ($variant->getType() == ProductVariantTypeEnum::IMAGE) {
                    if ($option == null) {
                        $optionObj['value'] = "//via.placeholder.com/1000x1000";
                    } else {
                        $optionObj['value'] = $this->assetsManager->getUrl($optionObj['value']);
                    }
                }
                $object["options"][] = $optionObj;
            }

            $variantObjects[] = $object;
        }
        return $variantObjects;
    }

    public function getEditOptionsForm(ProductVariant $variant, $page = 1): \stdClass
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->variant = $variant->getId();
        $search->ordr = ["column" => 0, "dir" => "DESC"];

        $count = $this->productVariantOptionRepository->filter($search, true);
        $paginator = new Paginator($count, $page, 20);
        $options = $this->productVariantOptionRepository->filter($search, false, $paginator->getLimitStart(),
            $paginator->getPageLimit());
        foreach ($options as $option) {
            $noOfUsage = $this->productPriceHasVariantOptionRepository->countByOption($option);
            $option->isUsed = $noOfUsage > 0;
        }
        $form = $this->createFormBuilder($variant)
            ->setAction($this->generateUrl("product_variant_option_edit", ["id" => $variant->getId(), "page" => $page]))
            ->add("options", CollectionType::class, [
                'entry_type' => ProductVariantOptionType::class,
                "data" => $options,
                "allow_delete" => false,
                "mapped" => false,
                "label" => false,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (array_key_exists('options', $data)) {
                    $options = $data['options'];
                    $sortedOptions = [];

                    foreach ($options as $option) {
                        $sortedOptions[] = $option;
                    }
                    $data['options'] = $sortedOptions;
                    $event->setData($data);
                }
            })
            ->getForm();

        $return = new \stdClass();
        $return->paginator = $paginator;
        $return->form = $form;

        return $return;
    }

    private function createFormBuilder($data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $data, $options);
    }

    private function generateUrl(
        $route,
        $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
