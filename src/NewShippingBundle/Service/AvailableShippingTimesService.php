<?php

namespace App\NewShippingBundle\Service;

use App\NewShippingBundle\Entity\Zone;
use App\NewShippingBundle\Repository\ShippingTimeRepository;
use App\ProductBundle\Entity\Product;

class AvailableShippingTimesService
{
    public function __construct(private readonly ShippingTimeRepository $shippingTimeRepository)
    {
    }

    /**
     * @param Product $product
     * @param Zone $targetZone (Ex. Egypt or Saudi Arabia )
     * @return array
     */
    public function getProductShippingTimes(Product $product, Zone $targetZone): array
    {
        $sourceZone = $product?->getStoreAddress()?->getZone();
        if (!$sourceZone instanceof Zone) {
            return [];
        }
        return $this->shippingTimeRepository->findShippingTimesBySourceZoneAndTargetZone($sourceZone,
            $targetZone);

    }
}
