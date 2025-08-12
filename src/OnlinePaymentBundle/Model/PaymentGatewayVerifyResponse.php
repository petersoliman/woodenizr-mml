<?php

namespace App\OnlinePaymentBundle\Model;

class PaymentGatewayVerifyResponse
{
    private ?string $paymentId = null;
    private ?string $merchantOrderId = null;
    private array $processData = [];
    private ?string $message = "Unspecified Error";
    private ?bool $success = false;


    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    public function getMerchantOrderId(): ?string
    {
        return $this->merchantOrderId;
    }


    public function setMerchantOrderId(?string $merchantOrderId): void
    {
        $this->merchantOrderId = $merchantOrderId;
    }

    public function getProcessData(): array
    {
        return $this->processData;
    }

    public function setProcessData(array $processData): void
    {
        $this->processData = $processData;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): void
    {
        $this->success = $success;
    }

}