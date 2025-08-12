<?php

namespace App\ECommerceBundle\Entity;

use App\NewShippingBundle\Entity\ShippingTime;
use App\ProductBundle\Entity\ProductPrice;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table("cart_has_product_price")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\CartHasProductPriceRepository")
 */
class CartHasProductPrice
{

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Cart", inversedBy="cartHasProductPrices")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Cart $cart = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductPrice")
     */
    private ?ProductPrice $productPrice = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\NewShippingBundle\Entity\ShippingTime")
     */
    private ?ShippingTime $shippingTime = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="qty", type="integer", nullable=false)
     */
    private int $qty = 0;

    /**
     * @ORM\Column(name="created", type="datetime")
     */
    private ?\DateTimeInterface $created = null;

    /**
     * @ORM\Column(name="modified", type="datetime")
     */
    private ?\DateTimeInterface $modified = null;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updatedTimestamps(): void
    {
        $this->setModified(new \DateTime(date('Y-m-d H:i:s')));

        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getPrice(): ?float
    {
        return $this->getProductPrice()->getSellPrice() * $this->getQty();
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProductPrice(): ?ProductPrice
    {
        return $this->productPrice;
    }

    public function setProductPrice(?ProductPrice $productPrice): self
    {
        $this->productPrice = $productPrice;

        return $this;
    }

    public function getShippingTime(): ?ShippingTime
    {
        return $this->shippingTime;
    }

    public function setShippingTime(?ShippingTime $shippingTime): static
    {
        $this->shippingTime = $shippingTime;

        return $this;
    }


}
