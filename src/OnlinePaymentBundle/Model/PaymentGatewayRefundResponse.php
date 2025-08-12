<?php

namespace App\OnlinePaymentBundle\Model;

class PaymentGatewayRefundResponse
{
    private ?string $paymentId = null;
    private array $sentData = [];
    private array $processData = [];
    private ?string $message = null;
    private ?bool $success = false;


    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    public function getSentData(): array
    {
        return $this->sentData;
    }

    public function setSentData(array $sentData): void
    {
        $this->sentData = $sentData;
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