<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Occasion;

    class UpdateOccasionProductNumber
{
    private int $occasionId;

    public function __construct(Occasion $occasion)
    {
        $this->occasionId = $occasion->getId();
    }

    public function getOccasionId(): int
    {
        return $this->occasionId;
    }
}