<?php

namespace App\CurrencyBundle\Service;


use App\ECommerceBundle\Entity\Order;
use App\ProductBundle\Entity\ProductPrice;

class GoogleAnalyticService
{
    public function purchase(Order $order): array
    {
        $actionField = [
            'id' => $order->getId(),
            'revenue' => $order->getTotalPrice(),
            'shipping' => $order->getShippingCost(),
        ];
        if ($order->getOrderHasCoupon() !== null) {
            $actionField['coupon'] = $order->getOrderHasCoupon()->getCode();
        }

        $products = [];
        foreach ($order->getOrderHasProductPrices() as $orderHasProductPrice) {
            $productObject = $this->getProductObject($orderHasProductPrice->getProductPrice(),
                $orderHasProductPrice->getQty());
            if (count($productObject) > 0) {
                $products[] = $productObject;
            }
        }

        return [
            'actionField' => $actionField,
            'products' => $products,
        ];
    }

    public function getProductObject(ProductPrice $productPrice = null, $qty = null): array
    {
        if ($productPrice == null) {
            return [];
        }
        $product = $productPrice->getProduct();
        $productName = $product->getTitle();
        $categoryName = null;
        if ($product->getCategory() != null) {
            $categoryName = $product->getCategory()->getTitle();
        }
        if (strlen($productPrice->getTitle()) > 0) {
            $productName .= " - ".$productPrice->getTitle();
        }

        $object = [
            'name' => $productName,
            'id' => $product->getId(),
            'price' => $productPrice->getSellPrice(),
            'category' => $categoryName,
        ];
        if ($qty > 0) {
            $object['quantity'] = $qty;
        }

        return $object;
    }
}