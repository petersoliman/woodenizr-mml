<?php

namespace App\ProductBundle\Messenger;

use App\ProductBundle\Entity\Collection;

class UpdateCollectionProductNumber
{
    private int $collectionId;

    public function __construct(Collection $collection)
    {
        $this->collectionId = $collection->getId();
    }

    public function getCollectionId(): int
    {
        return $this->collectionId;
    }
}