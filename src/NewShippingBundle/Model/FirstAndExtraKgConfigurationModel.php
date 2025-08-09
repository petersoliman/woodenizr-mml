<?php

namespace App\NewShippingBundle\Model;

use App\NewShippingBundle\Entity\ShippingZonePrice;

class FirstAndExtraKgConfigurationModel implements ConfigurableShippingPriceInterface
{
    private $firstNoOfKg = 1;
    private $firstKgRate;
    private $extraKgRate;
    /**
     * low el weight akber min el rakam da hay apply  $moreKgRate
     */
    private $moreThanKg;
    /**
     * low el weight akber min el rakam $moreThanKg hay apply el rate da
     */
    private $moreKgRate;
    /**
     * @var * Max weight ll shipment
     */
    private $maxWeight;

    public function __construct(ShippingZonePrice $shippingZonePrice)
    {
        $configuration = $shippingZonePrice->getConfiguration();
        if (array_key_exists("firstNoOfKg", $configuration)) {
            $this->firstNoOfKg = $configuration['firstNoOfKg'];
        }
        if (array_key_exists("firstKgRate", $configuration)) {
            $this->firstKgRate = $configuration['firstKgRate'];
        }
        if (array_key_exists("extraKgRate", $configuration)) {
            $this->extraKgRate = $configuration['extraKgRate'];
        }
        if (array_key_exists("moreThanKg", $configuration)) {
            $this->moreThanKg = $configuration['moreThanKg'];
        }
        if (array_key_exists("moreKgRate", $configuration)) {
            $this->moreKgRate = $configuration['moreKgRate'];
        }
        if (array_key_exists("maxWeight", $configuration)) {
            $this->maxWeight = $configuration['maxWeight'];
        }
    }

    public function getCalculator(): ?string
    {
        return ShippingZonePrice::CALCULATOR_FIRST_KG_EXTRA_KG;
    }

    public function hasRate(): bool
    {
        $hasRate = true;
        if (
            $this->getFirstKgRate() <= 0
            or $this->getFirstNoOfKg() <= 0
            or $this->getExtraKgRate() <= 0
        ) {
            $hasRate = false;
        }

        return $hasRate;
    }

    public function getConfiguration(): array
    {
        return [
            "firstNoOfKg" => $this->firstNoOfKg,
            "firstKgRate" => $this->firstKgRate,
            "extraKgRate" => $this->extraKgRate,
            "moreThanKg" => $this->moreThanKg,
            "moreKgRate" => $this->moreKgRate,
            "maxWeight" => $this->maxWeight,
        ];
    }

    /**
     * @return float
     */
    public function getFirstKgRate(): ?float
    {
        return $this->firstKgRate;
    }

    /**
     * @param float $firstKgRate
     */
    public function setFirstKgRate($firstKgRate): void
    {
        $this->firstKgRate = ($firstKgRate > 0) ? $firstKgRate : null;
    }

    /**
     * @return float
     */
    public function getFirstNoOfKg(): ?int
    {
        return $this->firstNoOfKg;
    }

    /**
     * @param int $firstNoOfKg
     */
    public function setFirstNoOfKg($firstNoOfKg): void
    {
        $this->firstNoOfKg = ($firstNoOfKg > 0) ? $firstNoOfKg : null;
    }

    /**
     * @return float
     */
    public function getExtraKgRate(): ?float
    {
        return $this->extraKgRate;
    }

    /**
     * @param float $extraKgRate
     */
    public function setExtraKgRate($extraKgRate): void
    {

        $this->extraKgRate = ($extraKgRate > 0) ? $extraKgRate : null;
    }

    /**
     * @return float
     */
    public function getMoreThanKg(): ?float
    {
        return ($this->moreThanKg != "") ? $this->moreThanKg : null;
    }

    /**
     * @param float $moreThanKg
     */
    public function setMoreThanKg($moreThanKg): void
    {
        $this->moreThanKg = ($moreThanKg > 0) ? $moreThanKg : null;
    }

    /**
     * @return float
     */
    public function getMoreKgRate(): ?float
    {
        return ($this->moreKgRate != "") ? $this->moreKgRate : null;
    }

    /**
     * @param float $moreKgRate
     */
    public function setMoreKgRate($moreKgRate): void
    {
        $this->moreKgRate = ($moreKgRate > 0) ? $moreKgRate : null;
    }

    /**
     * @return float
     */
    public function getMaxWeight(): ?float
    {
        return $this->maxWeight;
    }

    /**
     * @param float $maxWeight
     */
    public function setMaxWeight($maxWeight): void
    {
        $this->maxWeight = ($maxWeight > 0) ? $maxWeight : null;
    }
}