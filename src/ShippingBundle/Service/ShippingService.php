<?php

namespace App\ShippingBundle\Service;

use App\NewShippingBundle\Repository\ZoneRepository;
use App\ShippingBundle\Repository\CityRepository;

class ShippingService
{
    public function __construct(
//    private readonly   CityRepository $cityRepository,
    private readonly   ZoneRepository $zoneRepository
    )
    {
    }


    public function getZonesReadyToShipping(): array
    {
        return $this->zoneRepository->getZonesReadyToShipping();
    }

//    public function getZonesReadyToShipping(): array
//    {
//        return $this->cityRepository->getZonesReadyToShipping();
//    }


}