<?php

namespace App\OnlinePaymentBundle\Service;

use App\ECommerceBundle\Service\OrderPaymentRedirectService;
use App\ECommerceBundle\Service\OrderPaymentService;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use App\OnlinePaymentBundle\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Response;

class PaymentService
{
    private string $env;
    private EntityManagerInterface $em;
    private PaymentRepository $paymentRepository;
    private OrderPaymentRedirectService $orderPaymentRedirectService;


    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        OrderPaymentRedirectService $orderPaymentRedirectService
    ) {
        $this->em = $entityManager;
        $this->paymentRepository = $paymentRepository;
        $this->env = $_ENV['APP_ENV'];

        $this->orderPaymentRedirectService = $orderPaymentRedirectService;
    }

    /**
     * @param float $amount
     * @param string $userFirstName
     * @param string $userLastName
     * @param string $userEmail
     * @param string $userPhone
     * @param string $info (Subscription, add credit card, etc...)
     * @param string $gateway (paymob or aaib etc..)
     * @param PaymentTypesEnum $paymentType (order, subscription, credit card, etc ...)
     * @param int $paymentObjectId (orderId, userId of add credit card, subscription id, etc...)
     * @param PaymentMethod|null $paymentMethod
     * @param bool $isAutomated (make it true when making a payment without user intervention)
     * @return Payment
     */
    public function create(
        float $amount,
        string $userFirstName,
        string $userLastName,
        string $userEmail,
        string $userPhone,
        string $info,
        string $gateway,
        PaymentTypesEnum $paymentType,
        int $paymentObjectId,
        PaymentMethod $paymentMethod = null,
        bool $isAutomated = false
    ): Payment {
        if (!Validate::not_null($userLastName)) {
            $userLastName = "N/A";
        }
        $userData = [
            "firstName" => $userFirstName,
            "lastName" => $userLastName,
            "phone" => $userPhone,
            "email" => $userEmail,
        ];

        $payment = new Payment();
        $payment->setAmount($amount);
        $payment->setInfo($info);
        $payment->setType($paymentType);
        $payment->setObjectId($paymentObjectId);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setUserData($userData);
        $payment->setIsAutomated($isAutomated);
        $payment->setGateway($gateway);

        return $payment;
    }

    /**
     * Use this function to get redirect route to success or failure page after payment gateway callback
     * @param Payment $payment
     * @return Response
     * @throws \Exception
     */
    public function redirectAfterCallback(Payment $payment): Response
    {
        return match ($payment->getType()) {
            PaymentTypesEnum::ORDER => $this->orderPaymentRedirectService->orderRedirect($payment),
            default => throw new \Exception("Invalid Payment(getRedirectRoute function) PaymentId: ".$payment->getId()),
        };
    }
}
