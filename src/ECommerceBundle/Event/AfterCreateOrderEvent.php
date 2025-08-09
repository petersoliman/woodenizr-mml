<?php

namespace App\ECommerceBundle\Event;

use App\ECommerceBundle\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class AfterCreateOrderEvent extends Event
{
    public const NAME = 'after.create.order';

    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }
}