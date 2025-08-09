<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Event\ChangeOrderPaymentStatusEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChangeOrderPaymentStatusListener implements EventSubscriberInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeOrderPaymentStatusEvent::NAME => [
                ["changeOrderStatus", 1],
            ],
        ];
    }

    public function changeOrderStatus(ChangeOrderPaymentStatusEvent $event): void
    {
        $order = $event->getOrder();
        if (!$order instanceof Order) {
            return;
        }
        $order = $event->getOrder();

        $order->setPaymentState($event->getPaymentStatus());
        $this->em->persist($order);
        $this->em->flush();
    }

}