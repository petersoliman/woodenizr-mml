<?php

namespace App\NewShippingBundle\Service;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use App\NewShippingBundle\Model\SpecificWeightConfigurationModel;
use App\NewShippingBundle\Service\Model\CalculatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class CalculateShippingFeeSpecificWeightService implements CalculatorInterface
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

        $configuration = new SpecificWeightConfigurationModel($shippingZonePrice);

        $exchangeRate = $this->em->getRepository(ExchangeRate::class)->getExchangeRate($shippingZonePrice->getCurrency()->getId(),
            $userCurrency->getId(), true);

        $extraPrice = 0;
        if ($configuration->getExtraKgRate() > 0) {
            $extraPrice = ($exchangeRate * $configuration->getExtraKgRate());
        }
        $maxWeight = $this->em->getRepository(ShippingZonePriceSpecificWeight::class)->findOneBy([
            'shippingZonePrice' => $shippingZonePrice,
            "deleted" => null,
        ], ["weight" => "DESC"]);


//        todo : handel errors
        if ($maxWeight) {
            if ($totalProductPriceWeight >= $maxWeight->getWeight()) {
                $return->shippingFee += ($exchangeRate * $maxWeight->getRate());
                $totalProductPriceWeight -= $maxWeight->getWeight();
            } else {
                $weightRate = $this->em->getRepository(ShippingZonePriceSpecificWeight::class)->findGraterThanOrEqualWeightAndShippingZonePrice($shippingZonePrice,
                    $totalProductPriceWeight);
                if ($weightRate) {
                    $return->shippingFee += ($exchangeRate * $weightRate->getRate());
                    $totalProductPriceWeight -= $weightRate->getWeight();
                }
            }

            if ($totalProductPriceWeight > 0) {
                $totalProductPriceWeight = ceil($totalProductPriceWeight);
                $extraWeightPrice = $extraPrice * $totalProductPriceWeight;
                $return->shippingFee += $extraWeightPrice;
            }
        }

        return $return;
    }

    //    TODO: Handel validation in UI
    private function validate($shippingZonePrice, $totalProductPriceWeight): ?string
    {
        $configuration = new SpecificWeightConfigurationModel($shippingZonePrice);
        if ($configuration->getMaxWeight() > 0 and $configuration->getMaxWeight() <= $totalProductPriceWeight) {
            return "Max Weight exceeded, Please contact us";
        }

        return null;
    }
}