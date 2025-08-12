<?php

namespace App\CurrencyBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;

//use Symfony\Component\DependencyInjection\ContainerInterface;

class ExchangeRateService
{

    private EntityManagerInterface $em;
    private ?Currency $userCurrency=null;
    private mixed $exchangeRate;
    private UserCurrencyService $userCurrencyService;
    private ExchangeRateRepository $exchangeRateRepository;
    private CurrencyRepository $currencyRepository;
    private UpdateExchangeRateUsingAPIService $updateExchangeRateUsingAPIService;

    public function __construct(
        EntityManagerInterface            $em,
        UserCurrencyService               $userCurrencyService,
        ExchangeRateRepository            $exchangeRateRepository,
        CurrencyRepository                $currencyRepository,
        UpdateExchangeRateUsingAPIService $updateExchangeRateUsingAPIService
    )
    {
        $this->em = $em;
        $this->userCurrencyService = $userCurrencyService;
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->currencyRepository = $currencyRepository;
        $this->updateExchangeRateUsingAPIService = $updateExchangeRateUsingAPIService;
    }

    public function convertAmountUserCurrency(Currency $currency, $amount): float
    {
        $exchangeRate = $this->getExchangeRate($currency, $this->getUserCurrency());

        return $exchangeRate * $amount;
    }

    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency)
    {
        if (!isset($this->exchangeRate[$sourceCurrency->getId()][$targetCurrency->getId()])) {
            $exchangeRate = $this->exchangeRateRepository->getExchangeRate($sourceCurrency, $targetCurrency);
            $this->exchangeRate[$sourceCurrency->getId()][$targetCurrency->getId()] = $exchangeRate;
        }

        return $this->exchangeRate[$sourceCurrency->getId()][$targetCurrency->getId()];
    }

    public function moneyFormat($amount, $userCurrency = null, $withCurrency = true): string
    {
        if ($userCurrency == null) {
            $userCurrency = $this->getUserCurrency();
        }

        $numberDecimals = 0;
        if ($userCurrency->getCode() != "EGP") {
            $numberDecimals = 2;
        }
        $returnValue = number_format($amount, $numberDecimals);
        if ($withCurrency) {
            if ($userCurrency->getCode() != "EGP") {

                $returnValue .= " " . $userCurrency->getSymbol();
            } else {
                $returnValue = $userCurrency->getSymbol() . " " . $returnValue;
            }
        }

        return $returnValue;
    }

    public function addNewExchangeRates(): void
    {
        $sourceCurrencies = $this->currencyRepository->findAll();
        $targetCurrencies = $this->currencyRepository->findAll();

        foreach ($sourceCurrencies as $sourceCurrency) {
            foreach ($targetCurrencies as $targetCurrency) {
                $exchangeRate = $this->exchangeRateRepository->findOneBy([
                    "sourceCurrency" => $sourceCurrency,
                    "targetCurrency" => $targetCurrency,
                ]);
                if (!$exchangeRate instanceof ExchangeRate) {
                    $exchangeRate = new ExchangeRate();
                    $exchangeRate->setSourceCurrency($sourceCurrency);
                    $exchangeRate->setTargetCurrency($targetCurrency);

                    $ratio = $this->updateExchangeRateUsingAPIService->getExchangeRate($sourceCurrency,
                        $targetCurrency);
                    $exchangeRate->setRatio($ratio);
                    $this->em->persist($exchangeRate);
                }
            }
        }
        $this->em->flush();
    }

    public function getUserCurrency(): ?Currency
    {
        if (!$this->userCurrency instanceof Currency) {
            return $this->userCurrency = $this->userCurrencyService->getCurrency();
        }

        return $this->userCurrency;
    }
}
