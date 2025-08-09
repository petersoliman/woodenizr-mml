<?php

namespace App\CurrencyBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Repository\CurrencyRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class UserCurrencyService
{
    private ?Request $request;
    private CurrencyRepository $currencyRepository;
    private ?Currency $currentCurrency = null;
    private string $cookieAndSessionName = "user-currency";
    private int $cookieExpireInSec = 2592000; //month

    public function __construct(
        RequestStack       $requestStack,
        CurrencyRepository $currencyRepository
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->currencyRepository = $currencyRepository;
    }


    public function setCurrencyIfNotExist(Response $response, Currency $currency): void
    {
        if (!$this->hasCurrency()) {
            $this->setCurrency($response, $currency);
        }
    }

    public function setCurrency(Response $response, Currency $currency): void
    {
        $this->setCookie($response, $currency);
        $this->setSession($currency);
    }

    public function getCurrency(): Currency
    {
        if ($this->currentCurrency instanceof Currency) {
            return $this->currentCurrency;
        }
        $currency = null;
        if ($this->getRequest() instanceof Request) {
            $currencyCode = $this->getRequest()->cookies->get($this->cookieAndSessionName);
            if ($currencyCode) {
                $currency = $this->currencyRepository->findOneBy([
                    "code" => $currencyCode,
                    "deleted" => null,
                ]);
            }
        }
        if ($currency == null) {
            $currency = $this->currencyRepository->getDefaultCurrency();
        }
        $this->currentCurrency = $currency;
        return $this->currentCurrency;
    }

    public function hasCurrency(): bool
    {
        if ($this->getRequest() instanceof Request) {
            return $this->getRequest()->cookies->has($this->cookieAndSessionName);
        }
        return false;
    }


    private function setSession(Currency $currency): void
    {
        if ($this->getRequest() instanceof Request) {
            $this->getRequest()->getSession()->set($this->cookieAndSessionName, $currency->getCode());
        }
    }

    private function setCookie(Response $response, Currency $currency): void
    {
        $response->headers
            ->setCookie(
                new Cookie(
                    $this->cookieAndSessionName,
                    $currency->getCode(),
                    time() + $this->cookieExpireInSec,
                    '/',
                    null,
                    false,
                    true
                )
            );
    }


    private function getRequest(): ?Request
    {
        return $this->request;
    }
}
