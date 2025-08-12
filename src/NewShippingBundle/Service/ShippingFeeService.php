<?php

namespace App\NewShippingBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\CurrencyBundle\Entity\Currency;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\Zone;
use App\ProductBundle\Entity\ProductPrice;
use PN\ServiceBundle\Lib\Mailer;
use PN\ServiceBundle\Service\UserService;

class ShippingFeeService
{


    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CalculateShippingFeeFirstKgExtraKgService $calculateShippingFeeFirstKgExtraKgService,
        private readonly CalculateShippingFeeSpecificWeightService $calculateShippingFeeSpecificWeightService,
        private readonly AvailableShippingTimesService $availableShippingTimesService,
        private readonly UserService $userService,
    ) {
    }


    public function calculate(
        Zone $sourceZone,
        Zone $targetZone,
        ShippingTime $shippingTime,
        Currency $currency,
        $totalProductPriceWeight
    ): \stdClass {
        $return = new \stdClass();
        $return->shippingFee = 0;
        $return->errorMessage = null;

        $shippingZonePrice = $this->getShippingZonePrice($shippingTime, $sourceZone, $targetZone);
        if (!$shippingZonePrice) {
            $body = "No Shipping price - sourceZone: ".$sourceZone->getId().", targetZone= ".$targetZone->getId();
//            $this->sendErrorEmail($body);

            return $return;
        }
        switch ($shippingZonePrice->getCalculator()) {
            case  ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG;
                $return = $this->calculateShippingFeeFirstKgExtraKgService->calculate($shippingZonePrice, $currency,
                    $totalProductPriceWeight);
                break;
            case  ShippingZonePrice::CALCULATOR_WEIGHT_RATE;
                $return = $this->calculateShippingFeeSpecificWeightService->calculate($shippingZonePrice, $currency,
                    $totalProductPriceWeight);
                break;
            default:
                throw new \Exception("Invalid Calculator type");
                break;
        }

        return $return;
    }

    private function getShippingZonePrice(
        ShippingTime $shippingTime,
        Zone $sourceZone,
        Zone $targetZone
    ): ?ShippingZonePrice {

        $sourceShippingZone = $this->em->getRepository(ShippingZone::class)->getOneZone($sourceZone);
        $targetShippingZone = $this->em->getRepository(ShippingZone::class)->getOneZone($targetZone);

        if ($sourceShippingZone == null or $targetShippingZone == null) {
            return null;
        }
        $shippingZonePrice = $this->em->getRepository(ShippingZonePrice::class)->getOneBySourceZoneAndTargetZone($shippingTime,
            $sourceShippingZone,
            $targetShippingZone);

        if (!$shippingZonePrice) {
            $user = $this->userService->getUserName();
            $body = "No Shipping price - sourceZone:".$sourceZone->getId()." - targetZone:".$targetZone->getId()." - userName:".$user;
//            $this->sendErrorEmail($body);
        }

        return $shippingZonePrice;
    }

    //Done
    public function calculateShippingFeesByProductPrice(
        ProductPrice $productPrice,
        Zone $zoneTo,
        Currency $userCurrency,
        $qty
    ) {
        $productPriceWeight = $this->productPriceWeight($productPrice, $qty);

        $product = $productPrice->getProduct();
        $shippingTimes = $this->availableShippingTimesService->getProductShippingTimes($product, $zoneTo);
        $storeAddress = $product->getStoreAddress();
        if (count($shippingTimes) == 0) {
            return null;
        }
        if (!$storeAddress) {
            return null;
        }

        $prices = [];
        foreach ($shippingTimes as $shippingTime) {
            $calculateShippingFees = $this->calculate($storeAddress->getZone(), $zoneTo, $shippingTime,
                $userCurrency,
                $productPriceWeight);

            $prices[] = $calculateShippingFees->shippingFee;
        }

        sort($prices);

        return (count($prices) > 0) ? reset($prices) : null;
    }

    //Done
    public function productPriceWeight(ProductPrice $productPrice, $qty): float|int
    {
//        $isFreeShipping = $productPrice->getProduct()->getVendor()->getFreeShipping();
//        if ($isFreeShipping == false) {
            $productPriceWeight = ($productPrice->getWeight() > 0) ? $productPrice->getWeight() : 0;
//        } else {
//            $productPriceWeight = 0;
//        }

        return $productPriceWeight * $qty;
    }
}
