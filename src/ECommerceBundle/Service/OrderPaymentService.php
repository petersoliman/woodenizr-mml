<?php

namespace App\ECommerceBundle\Service;

use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Repository\OrderRepository;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Gateway\PaymobService;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use App\OnlinePaymentBundle\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class OrderPaymentService
{
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private PaymentRepository $paymentRepository;
    private PaymentService $paymentService;
    private PaymobService $paymobService;
    private OrderRepository $orderRepository;


    public function __construct(
        EntityManagerInterface $em,
        RouterInterface        $router,
        PaymentRepository      $paymentRepository,
        PaymentService         $paymentService,
        PaymobService          $paymobService, OrderRepository $orderRepository,
    )
    {
        $this->em = $em;
        $this->router = $router;
        $this->paymentRepository = $paymentRepository;
        $this->paymentService = $paymentService;
        $this->paymobService = $paymobService;
        $this->orderRepository = $orderRepository;
    }

    public function getUrlAfterCreateAnOrder(Order $order): ?string
    {
        $paymentMethodType = $order->getPaymentMethod()->getType();

        $url = null;

        switch ($paymentMethodType) {
            case PaymentMethodEnum::CASH_ON_DELIVERY:
                $url = $this->router->generate("fe_order_success_failure", ["success" => "true"],
                    UrlGeneratorInterface::ABSOLUTE_URL);
                break;
            /* case PaymentMethod::E_WALLET:
                 $payment = $this->generatePayment($order);
                 $url = $this->router->generate("paymob_mobile_wallet_number",
                     ["uuid" => $payment->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
                 break;*/
            case  PaymentMethodEnum::CREDIT_CARD:
            case  PaymentMethodEnum::ValU:
                $payment = $this->generatePayment($order);
                $url = $this->router->generate("fe_payment_paymob_pay", ["uuid" => $payment->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL);
                break;
        }

        return $url;
    }

    private function generatePayment(Order $order): Payment
    {
        $userFirstName = $this->splitFullName($order->getBuyerName())[0];
        $userLastName = $this->splitFullName($order->getBuyerName())[1];
        $userEmail = $order->getBuyerEmail();
        $userPhone = $order->getBuyerMobileNumber();

        return $this->createPayment(
            $order,
            $userFirstName,
            $userLastName,
            $userEmail,
            $userPhone,
            $this->paymobService->getProviderName());
    }

    private function splitFullName($name): array
    {
        $name = trim($name);
        $lastName = (!str_contains($name, ' ')) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $firstName = trim(preg_replace('#' . preg_quote($lastName, '#') . '#', '', $name));

        return [$firstName, $lastName];
    }

    private function createPayment(
        Order  $order,
        string $userFirstName,
        string $userLastName,
        string $userEmail,
        string $userPhone,
               $gateway
    ): Payment
    {
        $payment = $this->paymentRepository->getOneByTypeAndObjectId(PaymentTypesEnum::ORDER, $order->getId());
        $totalPrice = $this->orderRepository->getTotalPrice($order);
        if ($payment instanceof Payment and $payment->getAmount() == $totalPrice) {
            return $payment;
        }

        $info = "Order #" . $order->getId();
        $payment = $this->paymentService->create(
            amount: $totalPrice,
            userFirstName: $userFirstName,
            userLastName: $userLastName,
            userEmail: $userEmail,
            userPhone: $userPhone,
            info: $info,
            gateway: $gateway,
            paymentType: PaymentTypesEnum::ORDER,
            paymentObjectId: $order->getId(),
            paymentMethod: $order->getPaymentMethod()
        );
        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }
}