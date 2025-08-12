<?php

namespace App\ProductBundle\Messenger;

use App\VendorBundle\Entity\Vendor;

class IndexVendor
{
    private int $vendorId;

    public function __construct(Vendor $vendor)
    {
        $this->vendorId = $vendor->getId();
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }
}