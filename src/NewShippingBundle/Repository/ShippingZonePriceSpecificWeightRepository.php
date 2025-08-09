<?php

namespace App\NewShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ShippingZonePriceSpecificWeight|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingZonePriceSpecificWeight|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingZonePriceSpecificWeight[]    findAll()
 * @method ShippingZonePriceSpecificWeight[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingZonePriceSpecificWeightRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingZonePriceSpecificWeight::class, "t");
    }

    public function checkIfWeightExist(
        ShippingZonePrice               $shippingZonePrice,
                                        $weight,
        ShippingZonePriceSpecificWeight $shippingZonePriceSpecificWeight = null
    )
    {
        $statement = $this->createQueryBuilder('t')
            ->setMaxResults(1)
            ->where("t.deleted IS NULL")
            ->andWhere("t.shippingZonePrice = :shippingZonePriceId")
            ->andWhere("t.weight = :weight")
            ->setParameter("shippingZonePriceId", $shippingZonePrice->getId())
            ->setParameter("weight", $weight);

        if ($shippingZonePriceSpecificWeight != null) {
            $statement->andWhere('t.id != :id')
                ->setParameter("id", $shippingZonePriceSpecificWeight->getId());

        }

        return $statement->getQuery()->getOneOrNullResult();
    }

    public function findGraterThanOrEqualWeightAndShippingZonePrice(ShippingZonePrice $shippingZonePrice, $weight)
    {
        return $this->createQueryBuilder('t')
            ->setMaxResults(1)
            ->where("t.deleted IS NULL")
            ->andWhere("t.shippingZonePrice = :shippingZonePriceId")
            ->andWhere("t.weight >= :weight")
            ->setParameter("shippingZonePriceId", $shippingZonePrice->getId())
            ->setParameter("weight", $weight)
            ->getQuery()->getOneOrNullResult();
    }

}
