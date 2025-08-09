<?php

namespace App\BaseBundle;

enum ProductPriceTypeEnum: string
{
    case SINGLE_PRICE = "single-price";
    case MULTI_PRICES = "multi-prices";
    case VARIANTS = "variants";

    function hasTitle(): bool
    {
        return match ($this) {
            self::SINGLE_PRICE => false,
            self::MULTI_PRICES, self::VARIANTS => true,
        };
    }

    function allowDelete(): bool
    {
        return match ($this) {
            self::SINGLE_PRICE => false,
            self::MULTI_PRICES, self::VARIANTS => true,
        };
    }
}
