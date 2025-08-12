<?php

namespace App\CurrencyBundle\Controller\Administration;

use App\BaseBundle\Controller\AbstractController;
use App\BaseBundle\SystemConfiguration;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\CurrencyBundle\Repository\CurrencyRepository;
use App\CurrencyBundle\Repository\ExchangeRateRepository;
use App\CurrencyBundle\Service\UpdateExchangeRateUsingAPIService;
use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\UserService;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExchangeRateController extends AbstractController
{

    private array $currencies = [];
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        parent::__construct($em);
        $this->userService = $userService;
    }

    /**
     * @Route("/exchange/rate", name="exchange_rate", methods={"GET", "POST"})
     */
    public function index(
        Request $request,
        CurrencyRepository $currencyRepository,
        ExchangeRateRepository $exchangeRateRepository
    ): Response {
        if (!$this->validateSystemConfiguration()) {
            return $this->render('currency/admin/exchangeRate/index.html.twig', [
                'currencies' => [],
                'exchangeRates' => [],
            ]);
        }


        $this->currencies = $currencyRepository->findAll();

        if ($request->isMethod('POST')) {
            $this->save($request, $exchangeRateRepository);

            return $this->redirectToRoute('exchange_rate');
        }

        return $this->render('currency/admin/exchangeRate/index.html.twig', [
            'currencies' => $this->currencies,
            'exchangeRates' => $this->getExchangeRateInArray($exchangeRateRepository),
        ]);
    }

    /**
     * @Route("/exchange/update-api", name="exchange_rate_update_api", methods={"GET"})
     */
    public function updateRatesUsingApi(
        UpdateExchangeRateUsingAPIService $updateExchangeRateUsingAPIService,
    ): Response {
        $errors = $updateExchangeRateUsingAPIService->update();

        if (count($errors) == 0) {
            $this->addFlash("success", "The exchange rates successfully updated");
        } else {
            foreach ($errors as $error) {
                $this->addFlash("error", $error);
            }
        }

        return $this->redirectToRoute("exchange_rate");
    }

    private function getExchangeRateInArray(ExchangeRateRepository $exchangeRateRepository): array
    {
        $exchangeRates = $exchangeRateRepository->findAll();
        $exchangeRatesArr = [];
        foreach ($exchangeRates as $exchangeRate) {
            $exchangeRatesArr[$exchangeRate->getSourceCurrency()->getId()][$exchangeRate->getTargetCurrency()->getId()] = [
                "ratio" => $exchangeRate->getRatio(),
                "modified" => $exchangeRate->getModified(),
                "modifiedBy" => $exchangeRate->getModifiedBy(),
            ];
        }

        return $exchangeRatesArr;
    }

    private function save(Request $request, ExchangeRateRepository $exchangeRateRepository)
    {
        $ratio = $request->request->get('ratio');

        foreach ($ratio as $sourceCurrencyId => $data) {
            foreach ($data as $targetCurrencyId => $value) {
                if (!Validate::not_null($value)) {
                    continue;
                }

                $this->addOrUpdateExchangeRate($exchangeRateRepository, $sourceCurrencyId, $targetCurrencyId, $value);
            }
        }
        $this->addFlash("success", "Saved successfully");
    }

    private function addOrUpdateExchangeRate(
        ExchangeRateRepository $exchangeRateRepository,
        $sourceCurrencyId,
        $targetCurrencyId,
        $ratio,
    ) {

        $exchangeRate = $exchangeRateRepository->findOneBy([
            "sourceCurrency" => $sourceCurrencyId,
            "targetCurrency" => $targetCurrencyId,
        ]);

        if (!Validate::not_null($ratio) and $exchangeRate != null) {
            $this->em()->remove($exchangeRate);

            return;
        }

        if (!$exchangeRate) {
            $sourceCurrency = $this->getCurrencyById($sourceCurrencyId);
            $targetCurrency = $this->getCurrencyById($targetCurrencyId);
            $exchangeRate = new ExchangeRate();
            $exchangeRate->setSourceCurrency($sourceCurrency);
            $exchangeRate->setTargetCurrency($targetCurrency);
        }
        $exchangeRate->setRatio($ratio);
        $this->em()->persist($exchangeRate);
        $this->em()->flush();
    }

    /**
     * Optimization
     * Load Currency from private property instade of reload currency from database
     * @param $currencyId
     * @return Currency|null
     */
    private function getCurrencyById($currencyId): ?Currency
    {
        $currentCurrency = null;
        foreach ($this->currencies as $currency) {
            if ($currencyId == $currency->getId()) {
                $currentCurrency = $currency;
                break;
            }
        }

        return $currentCurrency;
    }

    private function validateSystemConfiguration(): bool
    {
        if (!SystemConfiguration::ENABLE_MULTI_CURRENCIES) {
            $this->addFlash("error", "The System Doesn't support multi currency");

            return false;
        }

        return true;
    }
}
