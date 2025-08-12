<?php

namespace App\NewShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\Zone;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

class ShippingTimeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingTime::class, "t");
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return $this->findOneBy(array('id' => $id, 'deleted' => false));
    }

    public function findAll(): array
    {
        return $this->findBy(array('deleted' => false));
    }

    public function removeShippingTimeByProduct($productId): void
    {
        $sql = " DELETE FROM product_has_shipping_time WHERE product_id=?";
        $this->getEntityManager()->getConnection()
            ->executeQuery($sql, array($productId));
    }

    public function findShippingTimesHasShippingPrice(Zone $sourceZone): array
    {
        $statement = $this->createQueryBuilder("st")
            ->leftJoin("st.shippingZonePrices", "szp")
            ->leftJoin("szp.sourceShippingZone", 'ssz')
            ->leftJoin('szp.targetShippingZone', 'tsz')
            ->leftJoin("ssz.zones", "sourceZones")
            ->leftJoin("tsz.zones", "targetZones")
            ->andWhere('sourceZones.deleted IS NULL')
            ->andWhere('targetZones.deleted IS NULL')
            ->andWhere('szp.hasRates = 1');

        $statement->andWhere('sourceZones.id = :sourceZoneId');
        $statement->setParameter("sourceZoneId", $sourceZone->getId());

        return $statement->getQuery()->getResult();
    }

    public function findShippingTimesBySourceZoneAndTargetZone(Zone $sourceZone, Zone $targetZone): array
    {
        $statement = $this->createQueryBuilder("st")
            ->leftJoin("st.shippingZonePrices", "szp")
            ->leftJoin("szp.sourceShippingZone", 'ssz')
            ->leftJoin('szp.targetShippingZone', 'tsz')
            ->leftJoin("ssz.zones", "sourceZones")
            ->leftJoin("tsz.zones", "targetZones")
            ->andWhere('sourceZones.deleted IS NULL')
            ->andWhere('targetZones.deleted IS NULL')
            ->andWhere('szp.hasRates = 1');

        $statement->groupBy("st.id");

        // Alex to Alex
        $statement->andWhere('sourceZones.id = :sourceZoneId')
            ->setParameter("sourceZoneId", $sourceZone->getId())
            ->andWhere('targetZones.id = :targetZone')
            ->setParameter("targetZone", $targetZone->getId());

        return $statement->getQuery()->getResult();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('t');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            't.id',
            't.title',
            't.noOfDeliveryDays',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }


    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('t.id LIKE :searchTerm '
                . 'OR t.name LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->deleted) and in_array($search->deleted, array(0, 1))) {
            if ($search->deleted == 1) {
                $statement->andWhere('t.deleted = 1');
            } else {
                $statement->andWhere('t.deleted = 0');
            }
        }
    }


    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count == true) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('t.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }

}
