<?php

namespace App\ProductBundle\Controller\FrontEnd;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\ProductPriceTypeEnum;
use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Service\ExchangeRateService;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Repository\ProductPriceHasVariantOptionRepository;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ProductBundle\Repository\ProductRepository;
use App\ProductBundle\Service\ProductVariantService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/variant")
 */
class ProductVariantController extends AbstractController
{

    /**
     * @Route("", name="fe_product_variant_get_data_ajax", methods={"GET"})
     */
    public function getAjax(
        Request                                $request,
        TranslatorInterface                    $translator,
        ProductVariantService                  $productVariantService,
        ExchangeRateService                    $exchangeRateService,
        ProductRepository                      $productRepository,
        ProductPriceRepository                 $productPriceRepository,
        ProductPriceHasVariantOptionRepository $productPriceHasVariantOptionRepository
    ): Response
    {
        $productId = $request->query->getInt("id");
        if (!Validate::not_null($productId)) {
            return $this->json(["error" => true, "message" => $translator->trans("product_not_found_msg")]);
        }
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->json(["error" => true, "message" => $translator->trans("product_not_found_msg")]);
        }

        $search = new \stdClass();
        $search->deleted = 0;
        $search->product = $product->getId();
        $search->hasPrice = true;
        $search->ordr = ["column" => 2, "dir" => "ASC"];
        $productPrices = $productPriceRepository->filter($search);

        $variantObjects = [];
        switch (SystemConfiguration::PRODUCT_PRICE_TYPE) {
            case ProductPriceTypeEnum::VARIANTS:
                $variantObjects = $productVariantService->getVariantsInObjectByProduct($product);
                break;
            case ProductPriceTypeEnum::MULTI_PRICES:
                $variantObjects = $this->getMultiPrices($translator, $product, $productPrices);
                break;
        }


        $variantOptions = $productPriceHasVariantOptionRepository->getVariantsByProductPrices($productPrices);
        $prices = [];
        foreach ($productPrices as $productPrice) {

            $object = $productPrice->getObj();
            $object['sellPrice'] = $exchangeRateService->convertAmountUserCurrency($productPrice->getCurrency(),
                $productPrice->getSellPrice());

            $object['originalPrice'] = $exchangeRateService->convertAmountUserCurrency($productPrice->getCurrency(),
                $productPrice->getUnitPriceWithCommission());
            $object["options"] = [];

            if (SystemConfiguration::PRODUCT_PRICE_TYPE == ProductPriceTypeEnum::VARIANTS) {
                $productPriceHasVariants = $this->getProductPriceHasVariantOptionByProductPrice($variantOptions, $productPrice);
                foreach ($productPriceHasVariants as $productPriceHasVariant) {
                    $object["options"][] = [
                        "id" => $productPriceHasVariant->getOption()->getId(),
                        "title" => $productPriceHasVariant->getOption()->gettitle(),
                    ];
                }
            } elseif (ProductPriceTypeEnum::MULTI_PRICES and count($productPrices) > 1) {
                $object["options"][] = [
                    "id" => $productPrice->getId(),
                    "title" => $productPrice->gettitle(),
                ];
            }

            $prices[] = $object;
        }
        return $this->json([
            "error" => false,
            "variants" => $variantObjects,
            "prices" => $prices
        ]);
    }

    private function getProductPriceHasVariantOptionByProductPrice(array $productPriceHasVariantOptions, ProductPrice $productPrice): array
    {
        $return = [];
        foreach ($productPriceHasVariantOptions as $productPriceHasVariantOption) {
            if ($productPriceHasVariantOption->getProductPrice()->getId() !== $productPrice->getId()) {
                continue;
            }
            $return[] = $productPriceHasVariantOption;
        }
        return $return;
    }

    /**
     * @param TranslatorInterface $translator
     * @param Product $product
     * @param array<ProductPrice> $productPrices
     * @return array|array[]
     */
    private function getMultiPrices(TranslatorInterface $translator, Product $product, array $productPrices): array
    {
        if (count($productPrices) == 1) {
            return [];
        }
        $variantObjects = [
            [
                "id" => $product->getId(),
                "title" => $translator->trans("options_txt"),
                "type" => "text",
                "options" => []
            ]
        ];
        foreach ($productPrices as $productPrice) {
            $variantObjects[0]["options"][] = [
                "id" => $productPrice->getId(),
                "title" => $productPrice->getTitle(),
                "value" => null,
            ];
        }
        return $variantObjects;
    }

}
