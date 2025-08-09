<?php

namespace App\ProductBundle\Entity;

use App\ContentBundle\Entity\Post;
use App\ContentBundle\Entity\Translation\PostTranslation;
use App\CurrencyBundle\Entity\Currency;
use App\ECommerceBundle\Entity\CouponHasProduct;
use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Translation\ProductTranslation;
use App\SeoBundle\Entity\Seo;
use App\ThreeSixtyViewBundle\Entity\ThreeSixtyView;
use App\VendorBundle\Entity\StoreAddress;
use App\VendorBundle\Entity\Vendor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PN\LocaleBundle\Model\LocaleTrait;
use PN\LocaleBundle\Model\Translatable;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Interfaces\UUIDInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\UuidTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;
use PN\ServiceBundle\Utils\SearchText;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="product", indexes={@ORM\Index(columns={"normalized_text"}, flags={"fulltext"})})
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductRepository")
 * @Gedmo\Loggable
 */
class Product implements Translatable, DateTimeInterface, UUIDInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        LocaleTrait,
        UuidTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity="\App\SeoBundle\Entity\Seo", cascade={"persist", "remove" })
     */
    private ?Seo $seo = null;

    /**
     * Short Description
     * Description
     * Gallery
     *
     * @ORM\OneToOne(targetEntity="\App\ContentBundle\Entity\Post", cascade={"persist", "remove" })
     */
    private ?Post $post = null;

    /**
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @ORM\ManyToOne(targetEntity="Category", cascade={"persist"})
     */
    private ?Category $category = null;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="App\VendorBundle\Entity\Vendor")
     */
    private ?Vendor $vendor = null;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="App\VendorBundle\Entity\StoreAddress")
     */
    private ?StoreAddress $storeAddress = null;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Brand")
     */
    private ?Brand $brand = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $currency = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\MediaBundle\Entity\Image", cascade={"persist"})
     * @ORM\JoinColumn(name="main_image_id", referencedColumnName="id", onDelete="SET NULL")
     */
    public ?Image $mainImage = null;

    /**
     * @ORM\OneToOne(targetEntity="App\ThreeSixtyViewBundle\Entity\ThreeSixtyView",  cascade={"persist", "remove" })
     */
    private ?ThreeSixtyView $threeSixtyView = null;

    /**
     * @Gedmo\Versioned
     * @Assert\NotNull
     * @ORM\Column(name="title", type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="sku", type="string", length=100, nullable=true)
     */
    private ?string $sku = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="tarteb", type="smallint", nullable=true)
     */
    private ?int $tarteb = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="search_terms", type="text", nullable=true)
     */
    private ?string $searchTerms = null;

    /**
     * @ORM\Column(name="normalized_text", type="text", nullable=true)
     */
    protected ?string $normalizedTxt = null;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="featured", type="boolean")
     */
    private bool $featured = false;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="new_arrival", type="boolean")
     */
    private bool $newArrival = false;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="enable_variants", type="boolean")
     */
    private bool $enableVariants = false;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="publish", type="boolean")
     */
    private bool $publish = true;

    /**
     * @Assert\NotNull
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductPrice", mappedBy="product", cascade={"persist"})
     */
    private Collection $prices;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductFavorite", mappedBy="product", cascade={"persist"})
     */
    private Collection $favorites;


    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\Translation\ProductTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    private Collection $translations;

    /**
     * @ORM\OneToOne(targetEntity="App\ProductBundle\Entity\ProductDetails", mappedBy="product", cascade={"ALL"}, orphanRemoval=true)
     */
    private ?ProductDetails $details = null;

    /**
     * @ORM\OneToMany(targetEntity="ProductHasAttribute", mappedBy="product", cascade={"persist"})
     */
    private Collection $productHasAttributes;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductHasCollection", mappedBy="product", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $productHasCollections;

    /**
     * @ORM\OneToMany(targetEntity="App\ProductBundle\Entity\ProductHasOccasion", mappedBy="product", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $productHasOccasions;
    /**
     * @ORM\OneToMany(targetEntity="App\ECommerceBundle\Entity\CouponHasProduct", mappedBy="product", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $couponHasProducts;

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getObj(): array
    {
        return [
            "title" => (string)$this->getTitle(),
            "newArrival" => (bool)$this->isNewArrival(),
            "featured" => (bool)$this->isFeatured(),
        ];
    }

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->productHasAttributes = new ArrayCollection();
        $this->productHasCollections = new ArrayCollection();
        $this->productHasOccasions = new ArrayCollection();
        $this->prices = new ArrayCollection();
        $this->couponHasProducts = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->updateNormalizedTxt();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->seo = clone $this->seo;

            $newPost = new Post();
            $newPost->setContent($this->post->getContent());
            foreach ($this->post->getTranslations() as $translation) {
                $newPostTrans = new PostTranslation();
                $newPostTrans->setContent($translation->getContent());
                $newPostTrans->setLanguage($translation->getLanguage());
                $newPost->addTranslation($newPostTrans);
            }
            $newPost->getImages()->clear();

            $this->post = $newPost;
            $details = clone $this->details;
            $details->setProduct($this);
            $this->details = $details;
            $translationsClone = new ArrayCollection();
            foreach ($this->getTranslations() as $translation) {
                $itemClone = clone $translation;
                $itemClone->setTranslatable($this);
                $translationsClone->add($itemClone);
            }
            $this->translations = $translationsClone;

            $pricesClone = new ArrayCollection();
            foreach ($this->getPrices() as $price) {
                $itemClone = clone $price;
                $itemClone->setProduct($this);
                $pricesClone->add($itemClone);
            }
            $this->prices = $pricesClone;

            $specsClone = new ArrayCollection();
            foreach ($this->getProductHasAttributes() as $productHasAttribute) {
                $itemClone = clone $productHasAttribute;
                $itemClone->setProduct($this);
                $specsClone->add($itemClone);
            }
            $this->productHasAttributes = $specsClone;

            $collectionsClone = new ArrayCollection();
            foreach ($this->getProductHasCollections() as $productHasCollection) {
                $itemClone = clone $productHasCollection;
                $itemClone->setProduct($this);
                $collectionsClone->add($itemClone);
            }
            $this->productHasCollections = $collectionsClone;

            $occasionsClone = new ArrayCollection();
            foreach ($this->getProductHasOccasions() as $productHasOccasion) {
                $itemClone = clone $productHasOccasion;
                $itemClone->setProduct($this);
                $occasionsClone->add($itemClone);
            }
            $this->productHasOccasions = $occasionsClone;
        }
    }

    /**
     * @return Collection|ProductPrice[]
     */
    public function getPrices(?int $unitPriceGreaterThan = null): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('deleted', null));

        if ($unitPriceGreaterThan !== null) {
            $criteria->andWhere(Criteria::expr()->gt('unitPrice', $unitPriceGreaterThan));
        }

        return $this->prices->matching($criteria);
    }

    public function removePrice(ProductPrice $productPrice): self
    {
        if ($this->prices->removeElement($productPrice)) {
            $productPrice->setDeleted(new \DateTime());
        }

        return $this;
    }

    private function updateNormalizedTxt()
    {
        $keywords = [
            $this->getTitle(),
            $this->getSku(),
            $this->getSearchTerms(),
        ];
        foreach ($this->getTranslations() as $translation) {
            $keywords[] = $translation->getTitle();
        }

        $searchableKeyword = SearchText::makeSearchableKeywords($keywords);
        $this->setNormalizedTxt($searchableKeyword);
    }

    public function getProductHasAttributesByAttributeId(Attribute $attribute): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('attribute', $attribute));

        return $this->productHasAttributes->matching($criteria);
    }


    public function getTitle(): ?string
    {
        return !$this->currentTranslation ? $this->title : $this->currentTranslation->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getTarteb(): ?int
    {
        return $this->tarteb;
    }

    public function setTarteb(?int $tarteb): self
    {
        $this->tarteb = $tarteb;

        return $this;
    }

    public function getSearchTerms(): ?string
    {
        return $this->searchTerms;
    }

    public function setSearchTerms(?string $searchTerms): self
    {
        $this->searchTerms = $searchTerms;

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

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;

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

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): self
    {
        $this->seo = $seo;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;

        return $this;
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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function addPrice(ProductPrice $price): self
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }

        return $this;
    }

    public function removeTranslation(ProductTranslation $translation): self
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getTranslatable() === $this) {
                $translation->setTranslatable(null);
            }
        }

        return $this;
    }

    public function getDetails(): ?ProductDetails
    {
        return $this->details;
    }

    public function setDetails(?ProductDetails $details): self
    {
        // unset the owning side of the relation if necessary
        if ($details === null && $this->details !== null) {
            $this->details->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($details !== null && $details->getProduct() !== $this) {
            $details->setProduct($this);
        }

        $this->details = $details;

        return $this;
    }

    /**
     * @return Collection<int, ProductHasAttribute>
     */
    public function getProductHasAttributes(): Collection
    {
        return $this->productHasAttributes;
    }

    public function addProductHasAttribute(ProductHasAttribute $productHasAttribute): self
    {
        if (!$this->productHasAttributes->contains($productHasAttribute)) {
            $this->productHasAttributes->add($productHasAttribute);
            $productHasAttribute->setProduct($this);
        }

        return $this;
    }

    public function removeProductHasAttribute(ProductHasAttribute $productHasAttribute): self
    {
        if ($this->productHasAttributes->removeElement($productHasAttribute)) {
            // set the owning side to null (unless already changed)
            if ($productHasAttribute->getProduct() === $this) {
                $productHasAttribute->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductHasCollection>
     */
    public function getProductHasCollections(): Collection
    {
        return $this->productHasCollections;
    }

    public function addProductHasCollection(ProductHasCollection $productHasCollection): self
    {
        if (!$this->productHasCollections->contains($productHasCollection)) {
            $this->productHasCollections->add($productHasCollection);
            $productHasCollection->setProduct($this);
        }

        return $this;
    }

    public function removeProductHasCollection(ProductHasCollection $productHasCollection): self
    {
        if ($this->productHasCollections->removeElement($productHasCollection)) {
            // set the owning side to null (unless already changed)
            if ($productHasCollection->getProduct() === $this) {
                $productHasCollection->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductHasOccasion>
     */
    public function getProductHasOccasions(): Collection
    {
        return $this->productHasOccasions;
    }

    public function addProductHasOccasion(ProductHasOccasion $productHasOccasion): self
    {
        if (!$this->productHasOccasions->contains($productHasOccasion)) {
            $this->productHasOccasions->add($productHasOccasion);
            $productHasOccasion->setProduct($this);
        }

        return $this;
    }

    public function removeProductHasOccasion(ProductHasOccasion $productHasOccasion): self
    {
        if ($this->productHasOccasions->removeElement($productHasOccasion)) {
            // set the owning side to null (unless already changed)
            if ($productHasOccasion->getProduct() === $this) {
                $productHasOccasion->setProduct(null);
            }
        }

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

    /**
     * @return Collection<int, CouponHasProduct>
     */
    public function getCouponHasProducts(): Collection
    {
        return $this->couponHasProducts;
    }

    public function addCouponHasProduct(CouponHasProduct $couponHasProduct): self
    {
        if (!$this->couponHasProducts->contains($couponHasProduct)) {
            $this->couponHasProducts->add($couponHasProduct);
            $couponHasProduct->setProduct($this);
        }

        return $this;
    }

    public function removeCouponHasProduct(CouponHasProduct $couponHasProduct): self
    {
        if ($this->couponHasProducts->removeElement($couponHasProduct)) {
            // set the owning side to null (unless already changed)
            if ($couponHasProduct->getProduct() === $this) {
                $couponHasProduct->setProduct(null);
            }
        }

        return $this;
    }

    public function isEnableVariants(): ?bool
    {
        return $this->enableVariants;
    }

    public function setEnableVariants(bool $enableVariants): self
    {
        $this->enableVariants = $enableVariants;

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

    /**
     * @return Collection<int, ProductFavorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(ProductFavorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setProduct($this);
        }

        return $this;
    }

    public function removeFavorite(ProductFavorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getProduct() === $this) {
                $favorite->setProduct(null);
            }
        }

        return $this;
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): static
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getStoreAddress(): ?StoreAddress
    {
        return $this->storeAddress;
    }

    public function setStoreAddress(?StoreAddress $storeAddress): static
    {
        $this->storeAddress = $storeAddress;

        return $this;
    }

    public function getThreeSixtyView(): ?ThreeSixtyView
    {
        return $this->threeSixtyView;
    }

    public function setThreeSixtyView(?ThreeSixtyView $threeSixtyView): static
    {
        $this->threeSixtyView = $threeSixtyView;

        return $this;
    }

}
