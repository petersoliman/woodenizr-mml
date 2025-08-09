<?php

namespace App\ECommerceBundle\Enum;

enum OrderStatusEnum: string
{
    case NEW = "new"; //
    case SUCCESS = "success";//
    case CANCELED = "canceled";//
    case PROCESSING = "processing";
    case FAILURE = "failure";//
    case POSTPONE = "postpone";//

    public function name(): string
    {
        return match ($this) {
            self::NEW => "New",
            self::SUCCESS => "Success",
            self::CANCELED => "Canceled",
            self::PROCESSING => "Processing",
            self::FAILURE => "Failure",
            self::POSTPONE => "Postpone",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => "#FF5722",
            self::SUCCESS => "#4CAF50",
            self::CANCELED => "#F44336",
            self::PROCESSING => "#2196F3",
            self::FAILURE => "#F44336",
            self::POSTPONE => "#00BCD4",
        };
    }
    public function decreaseOrIncreaseStockStock(): string{
        return match ($this) {
            self::NEW, self::POSTPONE, self::SUCCESS, self::PROCESSING => "decrease-stock",
            self::CANCELED, self::FAILURE => "increase-stock",
        };
    }
}
