<?php

namespace App\ECommerceBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\OnlinePaymentBundle\Entity\Payment;

class ChangeOrderStatusAfterOnlinePaymentEvent extends Event
{
    public const NAME = 'change.order.status.after.online.payment';

    private Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}