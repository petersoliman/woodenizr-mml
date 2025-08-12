<?php

namespace App\OnlinePaymentBundle\Model;

class PaymentGatewayPayResponse
{
    private ?string $paymentId = null;
    private array $sentData = [];
    private ?string $redirectUrl = null;
    private ?string $html = null;
    private bool $error = false;
    private ?string $errorMessage = null;


    public function isHTML(): bool
    {
        if ($this->html != null) {
            return true;
        }

        return false;
    }

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

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(?string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): void
    {
        $this->html = $html;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function setError(bool $error): void
    {
        $this->error = $error;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(bool $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}