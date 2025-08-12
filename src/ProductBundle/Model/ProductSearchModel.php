<?php

namespace App\ProductBundle\Model;

use App\CurrencyBundle\Entity\Currency;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Entity\ProductSearch;

class ProductSearchModel
{


    private Product $product;
    private int $id;
    private ?int $productPriceId = null;
    private string $title;
    private string $categoryTitle;
    private ?string $brandTitle = null;
    private string $slug;
    private ?Image $mainImage = null;
    private float $sellPrice;
    private float $originalPrice;
    private int $promotionPercentage = 0;
    private ?int $rate = 0;
    private Currency $currency;
    private bool $featured = false;
    private bool $newArrival = false;
    private bool $hasStock = true;
    private bool $hasFavorite = false;
    private bool $hasMultiPrice = false;
    private bool $enableAddToCart = false;


    public function __construct(
        ProductSearch $productSearch,
        string        $locale,
        int           $productId,
        ?string       $categoryTitle,
        ?string       $brandTitle,
        bool          $hasFavorite,
        ?int          $rate = 0
    )
    {
        $this->setProduct($productSearch->getProduct());
        $this->setId($productId);
        $this->setTitle($productSearch->getTitleByLocale($locale));
        $this->setCategoryTitle($categoryTitle);
        $this->setBrandTitle($brandTitle);
        $this->setSlug($productSearch->getSlugByLocale($locale));
        $this->setMainImage($productSearch->getMainImage());
        $this->setSellPrice($productSearch->getMinSellPrice());
        $this->setOriginalPrice($productSearch->getMinOriginalPrice());
        $this->setPromotionPercentage($productSearch->getPromotionPercentage());
        $this->setRate($rate);
        if ($productSearch->getProductPrice() instanceof ProductPrice) {
            $this->setProductPriceId($productSearch->getProductPrice()->getId());
        }
        $this->setCurrency($productSearch->getCurrency());
        $this->setFeatured($productSearch->isFeatured());
        $this->setNewArrival($productSearch->isNewArrival());
        $this->setHasStock($productSearch->isHasStock());
        $this->setHasMultiPrice($productSearch->isHasMultiPrice());
        $this->setHasFavorite($hasFavorite);

        if ($this->isHasStock()) {
            $this->setEnableAddToCart(true);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ProductSearchModel
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ProductSearchModel
    {
        $this->title = $title;

        return $this;
    }

    public function getCategoryTitle(): string
    {
        return $this->categoryTitle;
    }

    public function setCategoryTitle(string $categoryTitle): ProductSearchModel
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    public function getBrandTitle(): ?string
    {
        return $this->brandTitle;
    }

    public function setBrandTitle(?string $brandTitle): ProductSearchModel
    {
        $this->brandTitle = $brandTitle;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): ProductSearchModel
    {
        $this->slug = $slug;

        return $this;
    }

    public function getMainImage(): ?Image
    {
        return $this->mainImage;
    }

    public function setMainImage(?Image $mainImage): ProductSearchModel
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    public function getSellPrice(): float
    {
        return $this->sellPrice;
    }

    public function setSellPrice(float $originalPrice): ProductSearchModel
    {
        $this->sellPrice = $originalPrice;

        return $this;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(float $originalPrice): ProductSearchModel
    {
        $this->originalPrice = $originalPrice;

        return $this;
    }

    public function getPromotionPercentage(): int
    {
        return $this->promotionPercentage;
    }

    public function setPromotionPercentage($promotionPercentage): ProductSearchModel
    {
        $this->promotionPercentage = $promotionPercentage;

        return $this;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function setRate(?int $rate): ProductSearchModel
    {
        $this->rate = $rate;

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): ProductSearchModel
    {
        $this->currency = $currency;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): ProductSearchModel
    {
        $this->featured = $featured;

        return $this;
    }

    public function isNewArrival(): bool
    {
        return $this->newArrival;
    }

    public function setNewArrival(bool $featured): ProductSearchModel
    {
        $this->newArrival = $featured;

        return $this;
    }

    public function isHasStock(): bool
    {
        return $this->hasStock;
    }

    public function setHasStock(bool $hasStock): ProductSearchModel
    {
        $this->hasStock = $hasStock;

        return $this;
    }

    public function isHasFavorite(): bool
    {
        return $this->hasFavorite;
    }

    public function setHasFavorite(bool $hasFavorite): ProductSearchModel
    {
        $this->hasFavorite = $hasFavorite;

        return $this;
    }

    public function isHasMultiPrice(): bool
    {
        return $this->hasMultiPrice;
    }

    public function setHasMultiPrice(bool $hasMultiPrice): ProductSearchModel
    {
        $this->hasMultiPrice = $hasMultiPrice;

        return $this;
    }

    public function isEnableAddToCart(): bool
    {
        return $this->enableAddToCart;
    }

    public function setEnableAddToCart(bool $enableAddToCart): ProductSearchModel
    {
        $this->enableAddToCart = $enableAddToCart;

        return $this;
    }

    public function getProductPriceId(): ?int
    {
        return $this->productPriceId;
    }

    public function setProductPriceId(?int $productPriceId): ProductSearchModel
    {
        $this->productPriceId = $productPriceId;

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }
}
