<?php

namespace App\NewShippingBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Model\FirstAndExtraKgConfigurationModel;
use App\NewShippingBundle\Service\Model\CalculatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class CalculateShippingFeeFirstKgExtraKgService implements CalculatorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function calculate(
        ShippingZonePrice $shippingZonePrice,
        Currency          $userCurrency,
        float             $totalProductPriceWeight
    ): \stdClass
    {

        $return = new \stdClass();
        $return->shippingFee = 0;
        $return->errorMessage = null;

        $validate = $this->validate($shippingZonePrice, $totalProductPriceWeight);
        if ($validate != null) {
            $return->errorMessage = $validate;

            return $return;
        }

//        todo : handel errors
        $configuration = new FirstAndExtraKgConfigurationModel($shippingZonePrice);
        $exchangeRate = $this->em->getRepository(ExchangeRate::class)->getExchangeRate(
            $shippingZonePrice->getCurrency(),
            $userCurrency);

        $shippingItemFirstNumberOfKg = (!$configuration->getFirstNoOfKg()) ? 1 : $configuration->getFirstNoOfKg();
        $shippingItemFirstKg = (!$configuration->getFirstKgRate()) ? 0 : ($exchangeRate * $configuration->getFirstKgRate());
        $shippingItemExtraKg = (!$configuration->getExtraKgRate()) ? 0 : ($exchangeRate * $configuration->getExtraKgRate());

        if ($totalProductPriceWeight >= $configuration->getMoreThanKg() and $configuration->getMoreKgRate() > 0) { // heavy items
            $return->shippingFee = ($exchangeRate * $configuration->getMoreKgRate());
        } elseif ($totalProductPriceWeight > $shippingItemFirstNumberOfKg) {
            $extraWeights = $totalProductPriceWeight - $shippingItemFirstNumberOfKg;
            $extraWeightPrice = ceil($extraWeights) * $shippingItemExtraKg;
            $return->shippingFee = $shippingItemFirstKg + $extraWeightPrice;
        } elseif ($totalProductPriceWeight <= $shippingItemFirstNumberOfKg) {
            $return->shippingFee = $shippingItemFirstKg;
        }

        return $return;
    }

//    TODO: Handel validation in UI
    private function validate($shippingZonePrice, $totalProductPriceWeight): ?string
    {
        $configuration = new FirstAndExtraKgConfigurationModel($shippingZonePrice);
        if ($configuration->getMaxWeight() > 0 and $configuration->getMaxWeight() <= $totalProductPriceWeight) {
            return "Max Weight exceeded, Please contact us";
        }

        return null;

    }
}