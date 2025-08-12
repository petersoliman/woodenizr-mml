<?php

namespace App\OnlinePaymentBundle\Enum;

enum PaymentStatusEnum: string
{
    case PENDING = "pending";
    case PAID = "paid";
    case NOT_PAID = "not-paid";
    case REFUNDED = "refunded";

    // is method has third party like creditCard or Valu or Mobile Wallet, etc..
    public function color(): string
    {
        return match ($this) {
            self::PENDING => "#434b50",
            self::PAID => "#4CAF50",
            self::NOT_PAID => "#F44336",
            self::REFUNDED => "#2196F3",
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::PENDING => "Awaiting Payment",
            self::PAID => "Paid",
            self::NOT_PAID => "Not Paid",
            self::REFUNDED => "Refunded",
        };
    }
}
