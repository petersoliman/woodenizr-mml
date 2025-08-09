<?php

namespace App\ECommerceBundle\Enum;

enum CouponTypeEnum: string
{
    case PERCENTAGE = "percentage";
    case FIXED_AMOUNT = "fixed-amount";

    public function name(): string
    {
        return match ($this) {
            self::PERCENTAGE => "Percentage",
            self::FIXED_AMOUNT => "Fixed Amount",
        };
    }

    public function suffix(): string
    {
        return match ($this) {
            self::PERCENTAGE => "%",
            self::FIXED_AMOUNT => "EGP",
        };
    }
}
