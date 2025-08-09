<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Event\AfterCreateOrderEvent;
use App\ECommerceBundle\Service\OrderEmailService;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendEmailsAfterCreateOrderListener implements EventSubscriberInterface
{
    private OrderEmailService $orderEmailService;

    public function __construct(EntityManagerInterface $em, OrderEmailService $orderEmailService)
    {
        $this->orderEmailService = $orderEmailService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterCreateOrderEvent::NAME => [
                ["sendConfirmationEmailToUser", 1],
                ["sendConfirmationEmailToAdmin", 2],
            ],
        ];
    }

    public function sendConfirmationEmailToUser(AfterCreateOrderEvent $event): void
    {
        $order = $event->getOrder();
        if (!$order instanceof Order) {
            return;
        }
        if (!$this->isValidToSendEmails($order)) {
            return;
        }
        $this->orderEmailService->sendConfirmationEmailAfterCreateOrderToUser($order);
    }

    public function sendConfirmationEmailToAdmin(AfterCreateOrderEvent $event): void
    {
        $order = $event->getOrder();
        if (!$this->isValidToSendEmails($order)) {
            return;
        }
        $this->orderEmailService->sendConfirmationEmailAfterCreateOrderToAdmin($order);
    }


    private function isValidToSendEmails(?Order $order): bool
    {
        if (!$order instanceof Order) {
            return false;
        }
        if ($order->getState() !== OrderStatusEnum::NEW) {
            return false;
        }

        if (
            $order->getPaymentMethod()->getType()->isOnlinePaymentMethod()
            and (
                $order->getPaymentState() === PaymentStatusEnum::PENDING or $order->getPaymentState() === PaymentStatusEnum::NOT_PAID
            )
        ) {
            return false;
        }

        return true;
    }
}