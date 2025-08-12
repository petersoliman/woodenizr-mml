<?php

namespace App\NewShippingBundle\Model;

use Doctrine\Common\Collections\Collection;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;

class SpecificWeightConfigurationModel implements ConfigurableShippingPriceInterface
{
    private $extraKgRate;
    /**
     * @var * Max weight ll shipment
     */
    private $maxWeight;
    private $weights;

    public function __construct(ShippingZonePrice $shippingZonePrice)
    {
        $configuration = $shippingZonePrice->getConfiguration();
        if (array_key_exists("extraKgRate", $configuration)) {
            $this->extraKgRate = $configuration['extraKgRate'];
        }
        if (array_key_exists("maxWeight", $configuration)) {
            $this->maxWeight = $configuration['maxWeight'];
        }
        $this->weights = $shippingZonePrice->getSpecificWeights();

    }

    public function getCalculator(): ?string
    {
        return ShippingZonePrice::CALCULATOR_WEIGHT_RATE;
    }

    public function hasRate(): bool
    {
        $hasRate = true;
        if ($this->getWeights()->count() == 0) {
            $hasRate = false;
        }

        return $hasRate;
    }

    public function getConfiguration(): array
    {
        return [
            "extraKgRate" => $this->extraKgRate,
            "maxWeight" => $this->maxWeight,
        ];
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
        $this->extraKgRate = $extraKgRate;
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
        $this->maxWeight = $maxWeight;
    }

    /**
     * @return Collection|ShippingZonePriceSpecificWeight[]
     */
    public function getWeights(): Collection
    {
        return $this->weights;
    }

    public function addWeight(ShippingZonePriceSpecificWeight $specificWeight): self
    {
        if (!$this->weights->contains($specificWeight)) {
            $this->weights[] = $specificWeight;
            $specificWeight->setShippingZonePrice($this->shippingZonePrice);
        }

        return $this;
    }

    public function removeWeight(ShippingZonePriceSpecificWeight $specificWeight): self
    {
        if ($this->weights->removeElement($specificWeight)) {
            // set the owning side to null (unless already changed)
            if ($specificWeight->getShippingZonePrice() === $this) {
                $specificWeight->setShippingZonePrice(null);
            }
        }

        return $this;
    }

}