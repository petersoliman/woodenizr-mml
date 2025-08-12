<?php

namespace App\ProductBundle\DTO;

use App\BaseBundle\ProductPriceTypeEnum;
use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Service\ExchangeRateService;
use App\CurrencyBundle\Service\UserCurrencyService;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Entity\ProductVariantOption;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UrlService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartProductDTO
{
    private EntityManagerInterface $em;
    private UrlService $urlService;
    private ExchangeRateService $exchangeRateService;
    private UserCurrencyService $userCurrencyService;

    public function __construct(EntityManagerInterface $em, UrlService $urlService, ExchangeRateService $exchangeRateService, UserCurrencyService $userCurrencyService)
    {
        $this->em = $em;
        $this->urlService = $urlService;
        $this->exchangeRateService = $exchangeRateService;
        $this->userCurrencyService = $userCurrencyService;
    }

    public function getProduct(ProductPrice $productPrice): array
    {
        $product = $productPrice->getProduct();
        $object = $product->getObj();
        $mainImage = $product->getPost()->getMainImage();
        if ($mainImage != "") {
            $object['mainImage'] = $this->urlService->asset($mainImage->getAssetPath());
        } else {
            $object['mainImage'] = $this->urlService->asset("assets/img/product-img-placeholder.webp");
        }

        $object["absoluteUrl"] = $this->urlService->generateUrl('fe_product_show', ['slug' => $product->getSeo()->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

        $object['price'] = $this->getProductPrice($productPrice);

        $variants = [];

        if (SystemConfiguration::PRODUCT_PRICE_TYPE == ProductPriceTypeEnum::VARIANTS or $productPrice->getVariantOptionIds() != null) {
            $search = new \stdClass();
            $search->deleted = 0;
            $search->ids = explode("-", $productPrice->getVariantOptionIds());
            $variantOptions = $this->em->getRepository(ProductVariantOption::class)->filter($search);

            foreach ($variantOptions as $option) {
                $variants[] = $option->getVariant()->getTitle() . ": " . $option->getTitle();
            }
        }
        $object["variants"] = $variants;

        return $object;
    }

    private function getProductPrice(ProductPrice $price): array
    {
        $salePrice = $this->exchangeRateService->convertAmountUserCurrency($price->getCurrency(),
            $price->getSellPrice());


        $originalPrice = 0;
        if ($price->getSellPrice() < $price->getUnitPrice()) {
            $originalPrice = $this->exchangeRateService->convertAmountUserCurrency($price->getCurrency(),
                $price->getUnitPrice());
        }

        $userCurrency = $this->userCurrencyService->getCurrency();
        $numberDecimals = 0;
        if ($userCurrency->getCode() != "EGP") {
            $numberDecimals = 2;
        }


        return [
            "id" => (int)$price->getId(),
            "stock" => (int)$price->getStock(),
            "salePrice" => (double)round($salePrice, $numberDecimals),
            "originalPrice" => (double)round($originalPrice, $numberDecimals),
            "discountPercentage" => (int)$price->getPromotionalPercentage(),
        ];
    }
}