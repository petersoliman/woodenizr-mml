<?php

namespace App\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_has_attribute")
 * @ORM\Entity(repositoryClass="App\ProductBundle\Repository\ProductHasAttributeRepository")
 */
class ProductHasAttribute
{

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="productHasAttributes")
     */
    private ?Product $product = null;

    /**
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="productHasAttributes")
     */
    private ?Attribute $attribute = null;

    /**
     * @ORM\ManyToOne(targetEntity="SubAttribute", inversedBy="productHasAttributes")
     */
    private ?SubAttribute $subAttribute = null;

    /**
     * @ORM\Column(name="other_value", type="string", length=255, nullable=true)
     */
    private ?string $otherValue = null;

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

    public function getOtherValue(): ?string
    {
        return $this->otherValue;
    }

    public function setOtherValue(?string $otherValue): self
    {
        $this->otherValue = $otherValue;

        return $this;
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

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getSubAttribute(): ?SubAttribute
    {
        return $this->subAttribute;
    }

    public function setSubAttribute(?SubAttribute $subAttribute): self
    {
        $this->subAttribute = $subAttribute;

        return $this;
    }

}
