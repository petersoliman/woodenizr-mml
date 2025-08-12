<?php

namespace App\ProductBundle\DTO;

use App\ProductBundle\Entity\Product;

class CartItem
{

    private $product;
    private $qty = 0;
    private $unitPrice = 0;

    public function getTotalPrice(): float
    {
        return $this->getUnitPrice() * $this->getQty();
    }

    public function getObj(): array
    {
        return [
            "qty" => $this->getQty(),
            "unitPrice" => $this->getUnitPrice(),
            "totalPrice" => $this->getTotalPrice(),
        ];
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQty(): int
    {
        return $this->qty;
    }

    public function setQty(int $qty)
    {
        $this->qty = $qty;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice)
    {
        $this->unitPrice = $unitPrice;
    }


}
