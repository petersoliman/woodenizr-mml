<?php

namespace App\NewShippingBundle\Service\Model;

use App\CurrencyBundle\Entity\Currency;
use App\NewShippingBundle\Entity\ShippingZonePrice;

interface  CalculatorInterface
{
    public function calculate(
        ShippingZonePrice $shippingZonePrice,
        Currency $userCurrency,
       float $totalProductPriceWeight
    ): \stdClass;
}