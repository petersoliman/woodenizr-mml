<?php

namespace App\ProductBundle\DTO;

use App\MediaBundle\Entity\Image;
use App\ProductBundle\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class ProductDTOService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function get(Product $object)
    {
        $object->mainImage = $this->getMainImage($object);
        $object->images = $this->getImages($object);

        return $object;
    }

    public function getMainImage(Product $product): ?Image
    {
        if ($product->getPost()) {
            $mainImage = $product->getPost()->getMainImage();

            return ($mainImage instanceof Image) ? $mainImage : null;
        }

        return null;
    }

    private function getImages(Product $product): array
    {
        $images = [];
        if ($product->getPost()) {
            $images = $product->getPost()->getImages()->toArray();
        }

        return $images;
    }
}
