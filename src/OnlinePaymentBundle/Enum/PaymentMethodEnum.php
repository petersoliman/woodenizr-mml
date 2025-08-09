<?php

namespace App\OnlinePaymentBundle\Enum;

enum PaymentMethodEnum: string
{
    case CREDIT_CARD = 'credit-card';
    case CASH_ON_DELIVERY = 'cash-on-delivery';
    case ValU = 'valU';

    // is method has third party like creditCard or Valu or Mobile Wallet, etc..
    public function hasThirdParty(): bool
    {
        return match ($this) {
            self::CASH_ON_DELIVERY => false,
            self::CREDIT_CARD, self::ValU => true,
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::CASH_ON_DELIVERY => "Cash on delivery",
            self::CREDIT_CARD => "Credit Card",
            self::ValU => "ValU",
        };
    }

    public function isOnlinePaymentMethod(): bool
    {
        return match ($this) {
            self::CASH_ON_DELIVERY => false,
            self::CREDIT_CARD, self::ValU => true,
        };
    }
}
