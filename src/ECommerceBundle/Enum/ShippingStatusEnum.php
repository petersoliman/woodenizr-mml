<?php

namespace App\ECommerceBundle\Enum;

enum ShippingStatusEnum: string
{
    case AWAITING_PROCESSING = "awaiting-processing";
    case PROCESSING = "processing";
    case WILL_NOT_DELIVER = "will-not-deliver";
    case SHIPPED = "shipped";
    case RETURNED = "returned";

    public function name(): string
    {
        return match ($this) {
            self::AWAITING_PROCESSING => "Awaiting Processing",
            self::PROCESSING => "Processing",
            self::WILL_NOT_DELIVER => "Will Not Deliver",
            self::SHIPPED => "Shipped",
            self::RETURNED => "Returned",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AWAITING_PROCESSING => "#FF5722",
            self::PROCESSING => "#FF5722",
            self::WILL_NOT_DELIVER => "#999999",
            self::SHIPPED => "#4CAF50",
            self::RETURNED => "#999999",
        };
    }
}
