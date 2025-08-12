<?php

namespace App\ProductBundle\Service;

use App\CurrencyBundle\Service\ExchangeRateService;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Attribute;
use App\ProductBundle\Entity\Category;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductSearch;
use App\ProductBundle\Model\ProductSearchModel;
use App\ProductBundle\Repository\CategoryRepository;
use App\ProductBundle\Repository\ProductPriceRepository;
use App\ProductBundle\Repository\ProductSearchRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\LocaleBundle\Repository\LanguageRepository;
use PN\MediaBundle\Service\ImageWebPService;
use PN\ServiceBundle\Service\UrlService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductSearchService
{
    private ?array $languages = null;

    private EntityManagerInterface $em;
    private RouterInterface $router;
    private CategoryRepository $categoryRepository;
    private ProductSearchRepository $productSearchRepository;
    private ProductPriceRepository $productPriceRepository;
    private LanguageRepository $languageRepository;
    private ExchangeRateService $exchangeRateService;
    private ImageWebPService $imageWebPService;
    private UrlService $urlService;


    public function __construct(
        EntityManagerInterface  $entityManager,
        RouterInterface         $router,
        ProductSearchRepository $productSearchRepository,
        CategoryRepository      $categoryRepository,
        ProductPriceRepository  $productPriceRepository,
        LanguageRepository      $languageRepository,
        ExchangeRateService     $exchangeRateService,
        ImageWebPService        $imageWebPService,
        UrlService              $urlService
    )
    {
        $this->em = $entityManager;
        $this->router = $router;

        $this->productSearchRepository = $productSearchRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productPriceRepository = $productPriceRepository;
        $this->languageRepository = $languageRepository;
        $this->exchangeRateService = $exchangeRateService;
        $this->imageWebPService = $imageWebPService;
        $this->urlService = $urlService;
    }


    public function insertOrDeleteProductInSearch(Product $product): void
    {
        if ($this->isValidForSearch($product) === true) {
            $this->indexProduct($product);
        } else {
            $this->productSearchRepository->deleteByProduct($product);
        }
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isValidForSearch(Product $product): bool
    {
        if (count($product->getPrices(0)) < 1) {
            return false;
        }
        // check category is published
        if ($product->isPublish() === false or $product->isDeleted()) {
            return false;
        }

        $category = $product->getCategory();
        if ($category instanceof Category) {
            if (!$category->isPublish() or $category->isDeleted()) {
                return false;
            }
            if ($category->getParentConcatIds()) {
                $search = new \stdClass();
                $search->ids = explode(',', $category->getParentConcatIds());
                $search->publish = 0;
                $checkParentsCategories = $this->categoryRepository->filter($search, true);
                if ($checkParentsCategories > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function convertEntityToObject(ProductSearchModel $productSearchModel): array
    {
        $promotionPercentageColor = null;
        if ($productSearchModel->getPromotionPercentage() < 10) {
            $promotionPercentageColor = "bronze";
        } elseif ($productSearchModel->getPromotionPercentage() <= 20) {
            $promotionPercentageColor = "silver";
        } elseif ($productSearchModel->getPromotionPercentage() > 20) {
            $promotionPercentageColor = "gold";
        }
        $title = $productSearchModel->getTitle();
        if (mb_strlen($productSearchModel->getTitle()) > 100) {
            $title = mb_substr($productSearchModel->getTitle(), 0, 100) . "...";
        }

        $object = [
            "id" => $productSearchModel->getId(),
            "title" => $title,
            "category" => $productSearchModel->getCategoryTitle(),
            "productPriceId" => $productSearchModel->getProductPriceId(),
            "rate" => $productSearchModel->getRate(),
            "rateCount" => null,
            "hasMultiPrices" => $productSearchModel->isHasMultiPrice(),
            "promotionPercentage" => $productSearchModel->getPromotionPercentage(),
            "promotionPercentageColor" => $promotionPercentageColor,
            "absoluteUrl" => $this->router->generate('fe_product_show', ['slug' => $productSearchModel->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL),
            "addRemoveFromFavorite" => $this->router->generate('fe_product_add_to_favorite_ajax',
                ['slug' => $productSearchModel->getSlug()],
                UrlGeneratorInterface::ABSOLUTE_URL),
            "isHasStock" => $productSearchModel->isHasStock(),
            "enableAddToCart" => $productSearchModel->isEnableAddToCart(),
            "isFavorite" => $productSearchModel->isHasFavorite(),
        ];

        $convertedOriginalPrice = $this->exchangeRateService->convertAmountUserCurrency(
            $productSearchModel->getCurrency(),
            $productSearchModel->getOriginalPrice() - $productSearchModel->getSellPrice()
        );
        $object["priceSaved"] = $this->exchangeRateService->moneyFormat($convertedOriginalPrice);

        $convertedPrice = null;
        if ($productSearchModel->getSellPrice() > 0) {
            $convertedPrice = $this->exchangeRateService->convertAmountUserCurrency($productSearchModel->getCurrency(),
                $productSearchModel->getSellPrice());
            $convertedPrice = $this->exchangeRateService->moneyFormat($convertedPrice);
        }
        $object["sellPrice"] = $convertedPrice;

        if ($productSearchModel->getMainImage() instanceof Image) {
            $object["mainImage"] = $this->imageWebPService->convertToWebP(
                $this->urlService->asset($productSearchModel->getMainImage()->getAssetPath()), null, 150);
        } else {
            $object["mainImage"] = $this->urlService->asset("assets/img/product-img-placeholder.webp");
        }

        return $object;
    }


    private function indexProduct(Product $product): void
    {
        $productSearch = $this->productSearchRepository->find($product);

        if (!$productSearch) {
            $productSearch = new ProductSearch();
            $productSearch->setProduct($product);
        }
        $productSearch->setCurrency($product->getCurrency());
        $productSearch->setCategory($product->getCategory());
        $productSearch->setBrand($product->getBrand());
        $productSearch->setNormalizedTxt($product->getNormalizedTxt());

        $productSearch->setFeatured($product->isFeatured());
        $productSearch->setNewArrival($product->isNewArrival());

        $productSearch->setMainImage($product->getMainImage());
        $this->assignPriceToProductSearch($product, $productSearch);
        $this->assignSpecToProductSearch($product, $productSearch);
        $this->addTitles($product, $productSearch);
        $this->addSlugs($product, $productSearch);

        $this->em->persist($productSearch);
        $this->em->flush();
    }

    private function assignSpecToProductSearch(Product $product, ProductSearch $productSearch): void
    {
        $specs = [];
        $productSpecs = $product->getProductHasAttributes();
        foreach ($productSpecs as $productSpec) {
            $attribute = $productSpec->getAttribute();
            $subAttribute = $productSpec->getSubAttribute();
            if ($attribute->getSearch() === false) {
                continue;
            }
            if ($subAttribute == null) {
                if ($attribute->getType() == Attribute::TYPE_NUMBER) {
                    $otherValue = (float)$productSpec->getOtherValue();
                } else {
                    $otherValue = (string)$productSpec->getOtherValue();
                }
                $specs["attr_" . $attribute->getId()] = $otherValue;
            } else {
                $specs["attr_" . $attribute->getId()][] = $subAttribute->getId();
            }
        }
        $productSearch->setSpecs($specs);
    }

    private function assignPriceToProductSearch(Product $product, ProductSearch $productSearch): void
    {
        $productPrice = $this->productPriceRepository->getMinPrice($product);

        $originalPrices = [];
        $sellPrices = [];
        $stock = 0;
        $prices = $product->getPrices(0);
        foreach ($product->getPrices(0) as $price) {
            if ($price->getUnitPrice() == 0) {
                continue;
            }
            $originalPrices[] = $price->getUnitPrice();
            $sellPrices[] = $price->getSellPrice();
            $stock += $price->getStock();
        }
        $minSellPrice = min($sellPrices);
        $maxSellPrice = max($sellPrices);
        $minOriginalPrice = min($originalPrices);
        $maxOriginalPrice = max($originalPrices);

        $productSearch->setMinSellPrice($minSellPrice);
        $productSearch->setMaxSellPrice($maxSellPrice);
        $productSearch->setMinOriginalPrice($minOriginalPrice);
        $productSearch->setMaxOriginalPrice($maxOriginalPrice);
        if (count($prices) == 1) {
            $productSearch->setProductPrice($prices->first());
        }
        $offerExpiryDate = ($productPrice->hasPromotion() === true) ? $productPrice->getPromotionalExpiryDate() : null;
        $productSearch->setOfferExpiryDate($offerExpiryDate);
        $productSearch->setHasOffer($productPrice->hasPromotion());
        $productSearch->setHasMultiPrice(count($prices) > 1);


        $promotionPercentage = ($productPrice->hasPromotion() === true) ? $productPrice->getPromotionalPercentage() : 0;
        $productSearch->setPromotionPercentage($promotionPercentage);
        $productSearch->setHasStock($stock > 0);

    }


    private function addSlugs(Product $product, ProductSearch $productSearch): void
    {
        $seo = $product->getSeo();
        $slugs = [
            "en" => $seo->getSlug(),
        ];

        foreach ($this->getLanguages() as $language) {
            $slugs[$language->getLocale()] = $seo->getSlug();
        }

        foreach ($seo->getTranslations() as $translation) {
            $slugs[$translation->getLanguage()->getLocale()] = $translation->getSlug();
        }

        $productSearch->setSlugs($slugs);
    }

    private function addTitles(Product $product, ProductSearch $productSearch): void
    {
        $titles = [
            "en" => $product->getTitle(),
        ];

        foreach ($this->getLanguages() as $language) {
            $titles[$language->getLocale()] = $product->getTitle();
        }

        foreach ($product->getTranslations() as $translation) {
            $titles[$translation->getLanguage()->getLocale()] = $translation->getTitle();
        }

        $productSearch->setTitles($titles);
    }


    private function getLanguages(): array
    {
        if (is_array($this->languages)) {
            return $this->languages;
        }

        return $this->languages = $this->languageRepository->findAll();
    }
}
