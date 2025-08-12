<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\OrderRepository;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class OrderPaymentRedirectService
{
    private ?Request $request;
    private RouterInterface $router;
    private EntityManagerInterface $em;
    private OrderRepository $orderRepository;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        OrderRepository $orderRepository
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->orderRepository = $orderRepository;
    }

    public function orderRedirect(Payment $payment): RedirectResponse
    {
        if ($payment->getType() !== PaymentTypesEnum::ORDER) {
            return new RedirectResponse($this->router->generate("fe_home"), 301);
        }

        $order = $this->orderRepository->find($payment->getObjectId());
        if (!$order instanceof Order) {
            return new RedirectResponse($this->router->generate("fe_home"), 301);
        }

        $this->request->getSession()->set("orderId", $order->getId());

        $params = [
            "success" => $payment->isSuccess() ? "true" : "false",
            "message" => $payment->getTxnMessage(),
        ];
        $redirectRoute = $this->router->generate("fe_order_success_failure", $params);

        return new RedirectResponse($redirectRoute, 301);
    }
}