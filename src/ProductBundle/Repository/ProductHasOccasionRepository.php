<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ProductBundle\Entity\Occasion;
use App\ProductBundle\Entity\ProductHasOccasion;

class ProductHasOccasionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductHasOccasion::class, "pho");
    }

    public function removeProductByOccasion(Occasion $occasion)
    {
        return $this->createQueryBuilder("pho")
            ->delete()
            ->andWhere("pho.occasion = :occasionId")
            ->setParameter("occasionId", $occasion->getId())
            ->getQuery()->execute();
    }

}
