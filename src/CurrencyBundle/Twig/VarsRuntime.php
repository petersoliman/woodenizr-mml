<?php

namespace App\CurrencyBundle\Twig;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Service\ExchangeRateService;
use App\CurrencyBundle\Service\UserCurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface
{

    private ?Currency $userCurrency = null;
    private ExchangeRateService $exchangeRateService;
    private UserCurrencyService $userCurrencyService;

    public function __construct(
        ExchangeRateService $exchangeRateService,
        UserCurrencyService $userCurrencyService
    ) {
        $this->exchangeRateService = $exchangeRateService;
        $this->userCurrencyService = $userCurrencyService;
    }

    public function getUserCurrencySymbol(): ?string
    {
        $userCurrency = $this->getUserCurrency();

        return $userCurrency->getSymbol();
    }

    public function exchangeRateWithMoneyFormat($amount, Currency $currency, $withCurrency = true): string
    {
        $convertedAmount = $this->exchangeRateService->convertAmountUserCurrency($currency,
            $amount);

        return $this->moneyFormat($convertedAmount, $withCurrency);
    }

    public function moneyFormat($amount, bool $withCurrency = true): string
    {
        $userCurrency = $this->getUserCurrency();
        $numberDecimals = 0;
        if ($userCurrency->getCode() != "EGP") {
            $numberDecimals = 2;
        }
        $returnValue = number_format($amount, $numberDecimals);
        if ($withCurrency) {
            if ($userCurrency->getCode() == "EGP") {
                $returnValue .= " ".$userCurrency->getSymbol();
            } else {
                $returnValue = $userCurrency->getSymbol()." ".$returnValue;
            }
        }

        return $returnValue;
    }

    public function getUserCurrency(): Currency
    {
        if ($this->userCurrency == null) {
            $this->userCurrency = $this->userCurrencyService->getCurrency();
        }

        return $this->userCurrency;
    }
}
