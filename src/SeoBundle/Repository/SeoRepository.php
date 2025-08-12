<?php

namespace App\SeoBundle\Repository;

use App\SeoBundle\Entity\Seo;
use Doctrine\Persistence\ManagerRegistry;
use PN\SeoBundle\Repository\SeoRepository as BaseSeoRepository;

class SeoRepository extends BaseSeoRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seo::class);
    }
}
