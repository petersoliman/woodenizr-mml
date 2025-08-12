<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ProductBundle\Entity\Collection;
use App\ProductBundle\Entity\ProductHasCollection;

class ProductHasCollectionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductHasCollection::class, "phc");
    }

    public function removeProductByCollection(Collection $collection)
    {
        return $this->createQueryBuilder("phc")
            ->delete()
            ->andWhere("phc.collection = :collectionId")
            ->setParameter("collectionId", $collection->getId())
            ->getQuery()->execute();
    }

}
