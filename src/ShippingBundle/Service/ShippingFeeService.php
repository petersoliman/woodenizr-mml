<?php

namespace App\ShippingBundle\Service;

use App\CurrencyBundle\Service\ExchangeRateService;
use App\NewShippingBundle\Entity\Zone;
use App\ProductBundle\Entity\ProductPrice;
use App\ShippingBundle\Entity\City;

class ShippingFeeService
{

    public function __construct(
//      private readonly ExchangeRateService $exchangeRateService,
      private readonly \App\NewShippingBundle\Service\ShippingFeeService $shippingFeeService,

    )
    {
    }

    public function calculateShippingFeesByProductPrice(ProductPrice $productPrice, Zone $zoneTo, int $qty = 1): float
    {
        $this->shippingFeeService->calculateShippingFeesByProductPrice($productPrice, $zoneTo, $qty);
    }
    /*public function calculateShippingFeesByProductPrice(ProductPrice $productPrice, City $zoneTo, int $qty = 1): float
    {
        $totalShippingFee = $zoneTo->getPrice() * $qty;
        return $this->exchangeRateService->convertAmountUserCurrency($productPrice->getCurrency(), $totalShippingFee);
    }*/


}