<?php

namespace App\NewShippingBundle\Model;

interface  ConfigurableShippingPriceInterface
{
    public function getConfiguration(): array;

    public function getCalculator(): ?string;

    public function hasRate(): bool;


}