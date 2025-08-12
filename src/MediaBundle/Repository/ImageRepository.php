<?php

namespace App\MediaBundle\Repository;

use App\MediaBundle\Entity\Image;
use Doctrine\Persistence\ManagerRegistry;
use PN\MediaBundle\Repository\ImageRepository as BaseImageRepository;

class ImageRepository extends BaseImageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }
}
