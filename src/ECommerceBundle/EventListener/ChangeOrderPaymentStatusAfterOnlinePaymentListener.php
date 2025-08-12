<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Event\AfterCreateOrderEvent;
use App\ECommerceBundle\Event\ChangeOrderPaymentStatusEvent;
use App\ECommerceBundle\Event\ChangeOrderStatusAfterOnlinePaymentEvent;
use App\ECommerceBundle\Repository\OrderRepository;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChangeOrderPaymentStatusAfterOnlinePaymentListener implements EventSubscriberInterface
{
    private OrderRepository $orderRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        OrderRepository          $orderRepository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeOrderStatusAfterOnlinePaymentEvent::NAME => [
                ["changeOrderStatus", 1],
            ],
        ];
    }

    public function changeOrderStatus(ChangeOrderStatusAfterOnlinePaymentEvent $event): void
    {
        $payment = $event->getPayment();
        if ($payment->getType() !== PaymentTypesEnum::ORDER) {
            return;
        }

        $order = $this->orderRepository->find($payment->getObjectId());
        if (!$order instanceof Order) {
            return;
        }
        $paymentStatus = ($payment->isSuccess()) ? PaymentStatusEnum::PAID : PaymentStatusEnum::NOT_PAID;

        $orders = $this->orderRepository->getParentAndChildrenByParentOrder($order);
        foreach ($orders as $order) {
            $event = new ChangeOrderPaymentStatusEvent($order, $paymentStatus);
            $this->eventDispatcher->dispatch($event, ChangeOrderPaymentStatusEvent::NAME);


            $event = new AfterCreateOrderEvent($order);
            $this->eventDispatcher->dispatch($event, AfterCreateOrderEvent::NAME);
        }
    }
}