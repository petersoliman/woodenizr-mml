<?php

namespace App\CurrencyBundle\Service;

use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use Psr\Log\LoggerInterface;


class UpdateExchangeRateUsingAPIService
{
    private float $errorFactor = 1;
    //    private float $errorFactor = 1.05; // 5%
    private array $errors = [];
    private array $currencies = [];
    private array $currencyCodes = [];
    private EntityManagerInterface $em;
    private UserService $userService;
    private ExchangeRateRepository $exchangeRateRepository;
    private CurrencyRepository $currencyRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        UserService $userService,
        ExchangeRateRepository $exchangeRateRepository,
        CurrencyRepository $currencyRepository
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->userService = $userService;
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->currencyRepository = $currencyRepository;
    }

    public function update(): array
    {
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES) {
            return [];
        }

        $currencies = $this->getCurrencies();
        $countyCodes = $this->getCountyCodes();

        foreach ($countyCodes as $code) {
            $sourceCurrencyCode = $code;
            $exchangeRates = $this->getExchangeRates($sourceCurrencyCode, $countyCodes);
            if ($exchangeRates === null) {
                continue;
            }
            $sourceCurrency = $currencies[$sourceCurrencyCode];
            foreach ($exchangeRates as $apiCurrency => $apiRatio) {
                $targetCurrency = $currencies[$apiCurrency];
                $exchangeRate = $this->exchangeRateRepository->findOneBy([
                    "sourceCurrency" => $sourceCurrency,
                    "targetCurrency" => $targetCurrency,
                ]);

                if (!$exchangeRate) {
                    $exchangeRate = new ExchangeRate();
                    $exchangeRate->setSourceCurrency($sourceCurrency);
                    $exchangeRate->setTargetCurrency($targetCurrency);
                    $exchangeRate->setCreator($this->userService->getUserName());
                }
                $exchangeRate->setRatio(round($apiRatio * $this->errorFactor, 3));
                $exchangeRate->setModifiedBy($this->userService->getUserName());
                $this->em->persist($exchangeRate);
            }
            $this->em->flush();
        }

        return $this->errors;
    }

    private function getCurrencies(): array
    {
        if (count($this->currencies) > 0) {
            return $this->currencies;
        }
        $currencies = $this->currencyRepository->findAll();
        foreach ($currencies as $currency) {
            $this->currencies[$currency->getCode()] = $currency;
        }

        return $this->currencies;
    }

    private function getCountyCodes(): array
    {
        if (count($this->currencyCodes) > 0) {
            return $this->currencyCodes;
        }
        $currencies = $this->getCurrencies();
        foreach ($currencies as $currency) {
            $this->currencyCodes[] = $currency->getCode();
        }

        return $this->currencyCodes;
    }

    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency): float
    {
        $exchangeRates = $this->getExchangeRates($sourceCurrency->getCode(),
            [$targetCurrency->getCode()]);

        if (is_array($exchangeRates) and array_key_exists($targetCurrency->getCode(), $exchangeRates)) {
            return $exchangeRates[$targetCurrency->getCode()];
        }

        return 1;
    }

    public function getExchangeRates($sourceCurrencyCode, array $targetCurrencyCodes): ?array
    {
        $returnValues = [];
        $exchangeRates = $this->callExchangeAPI($sourceCurrencyCode, $targetCurrencyCodes);
        if ($exchangeRates === null) {
            return null;
        }
        foreach ($targetCurrencyCodes as $code) {
            if (array_key_exists($code, $exchangeRates)) {
                $returnValues[$code] = $exchangeRates[$code];

            } else {
                $returnValues[$code] = 1;
            }
        }

        return $returnValues;
    }

    private function callExchangeAPI($sourceCurrencyCode, array $targetCurrencyCodes): ?array
    {
        // set API Endpoint and API key
        if (!array_key_exists("APILAYER_APIKEY", $_ENV) or empty($_ENV["APILAYER_APIKEY"])) {
            throw new \Exception("Please enter APILAYER_APIKEY in '.env' file");
        }

        $access_key = $_ENV["APILAYER_APIKEY"];
        $data = [
            "base" => $sourceCurrencyCode,
            "symbols" => implode(",", $targetCurrencyCodes),
        ];
        $url = "https://api.apilayer.com/exchangerates_data/latest?".http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                "Content-Type: text/plain",
                "apikey: $access_key",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        // Decode JSON response:
        $exchangeRates = json_decode($response, true);

        if (is_array($exchangeRates) and array_key_exists("rates", $exchangeRates)) {
            // Access the exchange rate values, e.g. GBP:
            return $exchangeRates['rates'];
        }
        $errorMsg = "Can't convert $sourceCurrencyCode";
        if (is_array($exchangeRates) and array_key_exists("message", $exchangeRates)) {
            $errorMsg .= " - ".$exchangeRates['message'];
        }
        $this->errors[] = $errorMsg;

        $this->logger->critical("Exchange Rate Error: ", [
            "url" => $url,
            "response" => $response,
        ]);

        return null;
    }
}
