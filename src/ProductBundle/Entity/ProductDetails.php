<?php

namespace App\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Loggable
 * @ORM\Table(name="product_details")
 * @ORM\Entity()
 */
class ProductDetails
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity="\App\ProductBundle\Entity\Product", inversedBy="details")
     */
    private ?Product $product = null;

    /**
     * Augmented reality URl
     * @ORM\Column(name="augmented_reality_url", type="text", nullable=true)
     */
    private ?string $augmentedRealityUrl = null;

    /**
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(name="product_has_related_product",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="related_product_id", referencedColumnName="id")}
     *      )
     */
    private Collection $relatedProducts;

    public function __construct()
    {
        $this->relatedProducts = new ArrayCollection();
    }


    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(): Collection
    {
        return $this->relatedProducts;
    }

    public function addRelatedProduct(Product $relatedProduct): self
    {
        if (!$this->relatedProducts->contains($relatedProduct)) {
            $this->relatedProducts->add($relatedProduct);
        }

        return $this;
    }

    public function removeRelatedProduct(Product $relatedProduct): self
    {
        $this->relatedProducts->removeElement($relatedProduct);

        return $this;
    }

    public function getAugmentedRealityUrl(): ?string
    {
        return $this->augmentedRealityUrl;
    }

    public function setAugmentedRealityUrl(?string $augmentedRealityUrl): self
    {
        $this->augmentedRealityUrl = $augmentedRealityUrl;

        return $this;
    }

}
