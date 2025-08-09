<?php

namespace App\OnlinePaymentBundle\Interfaces;

use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Model\PaymentGatewayPayResponse;
use App\OnlinePaymentBundle\Model\PaymentGatewayVerifyResponse;
use Symfony\Component\HttpFoundation\Request;

interface PaymentGatewayInterface
{

    public function getProviderName(): string;

    public function pay(Payment $payment): PaymentGatewayPayResponse;

    public function verify(Request $request): PaymentGatewayVerifyResponse;
}