<?php

namespace App\ECommerceBundle\Entity;

use App\CurrencyBundle\Entity\Currency;
use App\ProductBundle\Entity\ProductPrice;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("order_has_product_price")
 * @ORM\Entity(repositoryClass="App\ECommerceBundle\Repository\OrderHasProductPriceRepository")
 */
class OrderHasProductPrice
{

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="orderHasProductPrices", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Order $order = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\ProductBundle\Entity\ProductPrice")
     */
    private ?ProductPrice $productPrice = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\CurrencyBundle\Entity\Currency")
     */
    private ?Currency $currency;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="qty", type="float")
     */
    private float $qty = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="unit_price_before_commission", type="float")
     */
    private ?float $unitPriceBeforeCommission = 0;

    /**
     * @ORM\Column(name="total_price_before_commission", type="float")
     */
    private ?float $totalPriceBeforeCommission = 0;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="unitPrice", type="float")
     */
    private ?float $unitPrice = 0;

    /**
     * @ORM\Column(name="total_price", type="float")
     */
    private ?float $totalPrice = 0;

    /**
     * el value batkon true lama bayts7ab min el stock bat3 el product
     * @ORM\Column(name="stock_withdrawn", type="boolean", options={"default" : 0})
     */
    private bool $stockWithdrawn = false;

    public function getQty(): ?float
    {
        return $this->qty;
    }

    public function setQty(float $qty): self
    {
        $this->qty = $qty;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function isStockWithdrawn(): ?bool
    {
        return $this->stockWithdrawn;
    }

    public function setStockWithdrawn(bool $stockWithdrawn): self
    {
        $this->stockWithdrawn = $stockWithdrawn;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getUnitPriceBeforeCommission(): ?float
    {
        return $this->unitPriceBeforeCommission;
    }

    public function setUnitPriceBeforeCommission(float $unitPriceBeforeCommission): static
    {
        $this->unitPriceBeforeCommission = $unitPriceBeforeCommission;

        return $this;
    }

    public function getTotalPriceBeforeCommission(): ?float
    {
        return $this->totalPriceBeforeCommission;
    }

    public function setTotalPriceBeforeCommission(float $totalPriceBeforeCommission): static
    {
        $this->totalPriceBeforeCommission = $totalPriceBeforeCommission;

        return $this;
    }


}
