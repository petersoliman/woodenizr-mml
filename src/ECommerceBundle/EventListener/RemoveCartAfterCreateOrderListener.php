<?php

namespace App\ECommerceBundle\EventListener;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Event\AfterCreateOrderEvent;
use App\ECommerceBundle\Repository\CartRepository;
use App\ECommerceBundle\Service\CartService;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoveCartAfterCreateOrderListener implements EventSubscriberInterface
{
    private CartService $cartService;
    private CartRepository $cartRepository;

    public function __construct( CartService $cartService, CartRepository $cartRepository)
    {
        $this->cartService = $cartService;
        $this->cartRepository = $cartRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterCreateOrderEvent::NAME => [
                ["removeCart", 5],
            ],
        ];
    }

    public function removeCart(AfterCreateOrderEvent $event): void
    {
        $order = $event->getOrder();
        if (!$this->isValidToRemoveCart($order) or !$order->getCartId()) {
            return;
        }

        $cart = $this->cartRepository->find($order->getCartId());
        $this->cartService->removeCart($cart);
    }

    private function isValidToRemoveCart(?Order $order): bool
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

        if (!Validate::not_null($order->getCartId())) {
            return false;
        }

        return true;
    }
}