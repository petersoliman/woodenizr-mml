<?php

namespace App\ECommerceBundle\Controller\FrontEnd;

use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\OrderRepository;
use App\ECommerceBundle\Service\CartService;
use App\ECommerceBundle\Service\FacebookConversionAPIService;
use App\ECommerceBundle\Service\OrderPaymentService;
use App\ECommerceBundle\Service\OrderService;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/order")
 */
class OrderController extends AbstractCartController
{

    const  CREATED_ORDER_ID_SESSION_NAME = "orderId";

    /**
     * @Route("/create", name="fe_order_create", methods={"POST"})
     */
    public function createOrder(
        Request             $request,
        CartService         $cartService,
        OrderService        $orderService,
        OrderPaymentService $orderPaymentService
    ): Response
    {
        $cart = $cartService->getCart();
        if (!$cart instanceof Cart) {
            throw $this->createNotFoundException();
        }

        $this->validateCouponAndRemoveIfNotValid($cart);


        $validateStock = $this->validateCartStock($cart);
        if ($validateStock instanceof RedirectResponse) {
            return $validateStock;
        }

        $validateShippingAddress = $this->validateShippingAddress($cart);
        if ($validateShippingAddress instanceof RedirectResponse) {
            return $validateShippingAddress;
        }

        $validatePaymentMethod = $this->validatePaymentMethod($cart);
        if ($validatePaymentMethod instanceof RedirectResponse) {
            return $validatePaymentMethod;
        }

        $validatePaymentMethod = $this->validateShippingTime($cart);
        if ($validatePaymentMethod instanceof RedirectResponse) {
            return $validatePaymentMethod;
        }

        $order = $orderService->createOrder($cart);
        if (!$order instanceof RedirectResponse and !$order instanceof Order) {
            return $this->redirectToRoute("fe_cart_show");
        } elseif ($order instanceof RedirectResponse) {
            return $order;
        }
        $request->getSession()->set(self::CREATED_ORDER_ID_SESSION_NAME, $order->getId());

        return $this->redirect($orderPaymentService->getUrlAfterCreateAnOrder($order));
    }

    /**
     * @Route("/status", name="fe_order_success_failure", methods={"GET"})
     */
    public function orderSuccessOrFailure(Request $request, FacebookConversionAPIService $facebookConversionAPIService, PaymentRepository $paymentRepository, OrderRepository $orderRepository): Response
    {
        $orderId = $request->getSession()->get(self::CREATED_ORDER_ID_SESSION_NAME);
        if (!Validate::not_null($orderId)) {
            throw $this->createNotFoundException();
        }
        $order = $orderRepository->find($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException();
        }
        $paymentMethodType = $order->getPaymentMethod()->getType();
        if (
            ($paymentMethodType == PaymentMethodEnum::CASH_ON_DELIVERY and $order->getPaymentState() == PaymentStatusEnum::NOT_PAID)
            or
            ($paymentMethodType->isOnlinePaymentMethod() and $order->getPaymentState() == PaymentStatusEnum::PAID)
        ) {
            $facebookConversionAPIService->sendEventByOrder($order, FacebookConversionAPIService::EVENT_PURCHASE);
            return $this->render("eCommerce/frontEnd/order/orderSuccessAndFailure.html.twig", [
                'order' => $order,
                "success" => true
            ]);
        }
        $payment = $paymentRepository->getOneByTypeAndObjectId(PaymentTypesEnum::ORDER, $order->getId());

        return $this->render("eCommerce/frontEnd/order/orderSuccessAndFailure.html.twig", [
            'order' => $order,
            "payment" => $payment,
            "success" => false,
        ]);
    }


}
