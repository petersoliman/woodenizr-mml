<?php

namespace App\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_price_has_variant_option")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductPriceHasVariantOptionRepository")
 */
class ProductPriceHasVariantOption
{

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductPrice")
     * @ORM\JoinColumn(name="product_price_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?ProductPrice $productPrice = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductVariant")
     */
    private ?ProductVariant $variant = null;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductVariantOption")
     */
    private ?ProductVariantOption $option = null;

    public function getProductPrice(): ?ProductPrice
    {
        return $this->productPrice;
    }

    public function setProductPrice(?ProductPrice $productPrice): self
    {
        $this->productPrice = $productPrice;

        return $this;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getOption(): ?ProductVariantOption
    {
        return $this->option;
    }

    public function setOption(?ProductVariantOption $option): self
    {
        $this->option = $option;

        return $this;
    }

}
