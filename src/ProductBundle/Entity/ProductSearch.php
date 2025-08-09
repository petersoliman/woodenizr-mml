<?php

namespace App\ProductBundle\Entity;

use App\CurrencyBundle\Entity\Currency;
use App\MediaBundle\Entity\Image;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("product_search", indexes={@ORM\Index(columns={"normalized_text"}, flags={"fulltext"})})
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductSearchRepository")
 */
class ProductSearch
{

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    private Product $product;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductPrice")
     */
    private ?ProductPrice $productPrice = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Category")
     */
    private Category $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\Brand")
     */
    private ?Brand $brand = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private Currency $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\MediaBundle\Entity\Image")
     * @ORM\JoinColumn(name="main_image_id", referencedColumnName="id", onDelete="SET NULL")
     */
    public ?Image $mainImage = null;

    /**
     * @ORM\Column(name="titles", type="json", nullable=false)
     */
    private ?array $titles = [];

    /**
     * @ORM\Column(name="slugs", type="json", nullable=false)
     */
    private ?array $slugs = [];


    /**
     * @ORM\Column(name="specs", type="json", nullable=true)
     */
    private ?array $specs;

    /**
     * @ORM\Column(name="min_sell_price", type="float")
     */
    private float $minSellPrice;
    /**
     * @ORM\Column(name="max_sell_price", type="float")
     */
    private float $maxSellPrice;

    /**
     * @ORM\Column(name="min_original_price", type="float")
     */
    private float $minOriginalPrice;

    /**
     * @ORM\Column(name="max_original_price", type="float")
     */
    private float $maxOriginalPrice;

    /**
     * @ORM\Column(name="normalized_text", type="text", nullable=true)
     */
    private ?string $normalizedTxt;

    /**
     * @ORM\Column(name="has_multi_price", type="boolean")
     */
    private bool $hasMultiPrice = false;

    /**
     * @ORM\Column(name="featured", type="boolean")
     */
    private bool $featured = false;

    /**
     * @ORM\Column(name="recommended_sort", type="smallint")
     */
    private int $recommendedSort = 0;

    /**
     * @ORM\Column(name="new_arrival", type="boolean")
     */
    private bool $newArrival = false;


    /**
     * @ORM\Column(name="has_offer", type="boolean")
     */
    private bool $hasOffer = false;

    /**
     * @ORM\Column(name="offer_expiry_date", type="date", nullable=true)
     */
    private ?\DateTimeInterface $offerExpiryDate;

    /**
     * @ORM\Column(name="promotion_percentage", type="float", nullable=true)
     */
    private float $promotionPercentage = 0;

    /**
     * @ORM\Column(name="has_stock", type="boolean", options={"default" : 1})
     */
    private bool $hasStock = true;

    /**
     * @ORM\Column(name="last_update_datetime", type="datetime")
     */
    private ?\DateTimeInterface $lastUpdate;

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setLastUpdate(new \DateTime(date('Y-m-d H:i:s')));
        $this->calculateRecommendedSort();
    }

    private function calculateRecommendedSort(): void
    {
        $score = 0;
        if ($this->isFeatured()) {
            $score += 1;
        }
        $this->setRecommendedSort($score);
    }

    public function getSlugByLocale(string $locale): ?string
    {
        if (array_key_exists($locale, $this->getSlugs())) {
            return $this->slugs[$locale];
        }
        if (count($this->slugs) > 0) {
            return reset($this->slugs);
        }
        return null;
    }

    public function getTitleByLocale(string $locale): ?string
    {
        if (array_key_exists($locale, $this->getTitles())) {
            return $this->titles[$locale];
        }
        if (count($this->titles) > 0) {
            return reset($this->titles);
        }
        return null;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSpecs(): array
    {
        return $this->specs;
    }

    public function setSpecs(?array $specs): self
    {
        $this->specs = $specs;

        return $this;
    }

    public function getMinSellPrice(): ?float
    {
        return $this->minSellPrice;
    }

    public function setMinSellPrice(float $minSellPrice): self
    {
        $this->minSellPrice = $minSellPrice;

        return $this;
    }

    public function getMaxSellPrice(): ?float
    {
        return $this->maxSellPrice;
    }

    public function setMaxSellPrice(float $maxSellPrice): self
    {
        $this->maxSellPrice = $maxSellPrice;

        return $this;
    }

    public function getMinOriginalPrice(): ?float
    {
        return $this->minOriginalPrice;
    }

    public function setMinOriginalPrice(float $minOriginalPrice): self
    {
        $this->minOriginalPrice = $minOriginalPrice;

        return $this;
    }

    public function getMaxOriginalPrice(): ?float
    {
        return $this->maxOriginalPrice;
    }

    public function setMaxOriginalPrice(float $maxOriginalPrice): self
    {
        $this->maxOriginalPrice = $maxOriginalPrice;

        return $this;
    }

    public function getNormalizedTxt(): ?string
    {
        return $this->normalizedTxt;
    }

    public function setNormalizedTxt(?string $normalizedTxt): self
    {
        $this->normalizedTxt = $normalizedTxt;

        return $this;
    }

    public function isHasMultiPrice(): ?bool
    {
        return $this->hasMultiPrice;
    }

    public function setHasMultiPrice(bool $hasMultiPrice): self
    {
        $this->hasMultiPrice = $hasMultiPrice;

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;

        return $this;
    }

    public function getRecommendedSort(): ?int
    {
        return $this->recommendedSort;
    }

    public function setRecommendedSort(int $recommendedSort): self
    {
        $this->recommendedSort = $recommendedSort;

        return $this;
    }

    public function isNewArrival(): ?bool
    {
        return $this->newArrival;
    }

    public function setNewArrival(bool $newArrival): self
    {
        $this->newArrival = $newArrival;

        return $this;
    }

    public function isHasOffer(): ?bool
    {
        return $this->hasOffer;
    }

    public function setHasOffer(bool $hasOffer): self
    {
        $this->hasOffer = $hasOffer;

        return $this;
    }

    public function isHasStock(): ?bool
    {
        return $this->hasStock;
    }

    public function setHasStock(bool $hasStock): self
    {
        $this->hasStock = $hasStock;

        return $this;
    }

    public function getOfferExpiryDate(): ?\DateTimeInterface
    {
        return $this->offerExpiryDate;
    }

    public function setOfferExpiryDate(?\DateTimeInterface $offerExpiryDate): self
    {
        $this->offerExpiryDate = $offerExpiryDate;

        return $this;
    }

    public function getPromotionPercentage(): ?float
    {
        return $this->promotionPercentage;
    }

    public function setPromotionPercentage(?float $promotionPercentage): self
    {
        $this->promotionPercentage = $promotionPercentage;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMainImage(): ?Image
    {
        return $this->mainImage;
    }

    public function setMainImage(?Image $mainImage): self
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    public function getTitles(): array
    {
        return $this->titles;
    }

    public function setTitles(array $titles): self
    {
        $this->titles = $titles;

        return $this;
    }

    public function getSlugs(): array
    {
        return $this->slugs;
    }

    public function setSlugs(array $slugs): self
    {
        $this->slugs = $slugs;

        return $this;
    }

    public function getProductPrice(): ?ProductPrice
    {
        return $this->productPrice;
    }

    public function setProductPrice(?ProductPrice $productPrice): static
    {
        $this->productPrice = $productPrice;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

}
