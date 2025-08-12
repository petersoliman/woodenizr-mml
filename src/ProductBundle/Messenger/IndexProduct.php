<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Product;

class IndexProduct
{
    private int $productId;

    public function __construct(Product $product)
    {
        $this->productId = $product->getId();
    }

    public function getProductId(): int
    {
        return $this->productId;
    }
}