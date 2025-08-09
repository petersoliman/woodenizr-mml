<?php

namespace App\ECommerceBundle\Event;

use App\ECommerceBundle\Entity\Order;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use Symfony\Contracts\EventDispatcher\Event;

class ChangeOrderPaymentStatusEvent extends Event
{
    public const NAME = 'change.order.payment.status';

    private Order $order;
    private PaymentStatusEnum $paymentStatus;

    public function __construct(Order $order, PaymentStatusEnum $paymentStatus)
    {
        $this->order = $order;
        $this->paymentStatus = $paymentStatus;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getPaymentStatus(): PaymentStatusEnum
    {
        return $this->paymentStatus;
    }
}