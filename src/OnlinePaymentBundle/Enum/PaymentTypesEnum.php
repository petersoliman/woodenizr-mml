<?php

namespace App\OnlinePaymentBundle\Enum;

enum PaymentTypesEnum: string
{
    case ORDER = "order";
    case ADD_CREDIT_CARD = "add-credit-card";

    public function name(): string
    {
        return match ($this) {
            self::ORDER => "Order",
            self::ADD_CREDIT_CARD => "Add Credit Card",
        };
    }
}
