<?php

namespace App\NewShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\NewShippingBundle\Entity\ShippingTime;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\ShippingZonePriceSpecificWeight;
use App\NewShippingBundle\Entity\Zone;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method ShippingZonePrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingZonePrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingZonePrice[]    findAll()
 * @method ShippingZonePrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingZonePriceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingZonePrice::class, "szp");
    }
    public function getOneBySourceZoneAndTargetZone(
        ShippingTime $shippingTime,
        ShippingZone $sourceShippingZone,
        ShippingZone $targetShippingZone
    ): ?ShippingZonePrice {
        return $this->createQueryBuilder('szp')
            ->addSelect("ssz, tsz, cur")
            ->leftJoin('szp.currency', 'cur')
            ->leftJoin('szp.sourceShippingZone', 'ssz')
            ->leftJoin('szp.targetShippingZone', 'tsz')
            ->andWhere("ssz.id= :sourceShippingZoneId")
            ->andWhere("tsz.id= :targetShippingZoneId")
            ->andWhere("szp.shippingTime = :shippingTimeId")
            ->setParameter("sourceShippingZoneId", $sourceShippingZone->getId())
            ->setParameter("targetShippingZoneId", $targetShippingZone->getId())
            ->setParameter("shippingTimeId", $shippingTime->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteByTargetRootZoneAndShippingTimeAndCalculator(
        ShippingTime $shippingTime,
        $calculator
    ): void
    {
        $ids = $this->_em->createQueryBuilder()
            ->from(ShippingZonePrice::class, "szp")
            ->select("DISTINCT szp.id")
            ->leftJoin('szp.targetShippingZone', 'tsz')
            ->leftJoin('tsz.zones', 'tszs')
            ->andWhere("szp.shippingTime = :shippingTimeId")
            ->andWhere('szp.calculator = :calculator')
            ->setParameter('calculator', $calculator)
            ->setParameter("shippingTimeId", $shippingTime->getId())
            ->getQuery()->getResult();

        $reformattedIds = array_map(function ($arr) {
            return $arr['id'];
        }, $ids);

        if (count($reformattedIds) > 0) {
            $this->_em->createQueryBuilder('szpw')
                ->delete()
                ->from(ShippingZonePriceSpecificWeight::class, "szpw")
                ->where('szpw.shippingZonePrice in (:ids)')
                ->setParameter('ids', $reformattedIds)
                ->getQuery()
                ->execute();
            $this->createQueryBuilder('szp')
                ->delete()
                ->where('szp.id in (:ids)')
                ->setParameter('ids', $reformattedIds)
                ->getQuery()
                ->execute();
        }

    }


    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('szp')
            ->addSelect("ssz, tsz, cou, cur")
            ->leftJoin('szp.sourceShippingZone', 'ssz')
            ->leftJoin('szp.targetShippingZone', 'tsz')
            ->leftJoin('tsz.zones', 'tszs')
            ->leftJoin('szp.courier', 'cou')
            ->leftJoin('szp.currency', 'cur');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'ssz.title',
            'tsz.title',
            'szp.calculator',
            'cur.title',
            'cou.title',
            't.created',
            't.creator',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) AND Validate::not_null($search->string)) {
            $statement->andWhere('szp.id LIKE :searchTerm '
                .'OR ssz.title LIKE :searchTerm '
                .'OR tsz.title LIKE :searchTerm '
                .'OR cur.title LIKE :searchTerm '
                .'OR cou.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->calculator) and Validate::not_null($search->calculator)) {
            $statement->andWhere('szp.calculator = :calculator');
            $statement->setParameter('calculator', $search->calculator);
        }
        if (isset($search->sourceShippingZone) and Validate::not_null($search->sourceShippingZone)) {
            $statement->andWhere('szp.sourceShippingZone = :sourceShippingZone');
            $statement->setParameter('sourceShippingZone', $search->sourceShippingZone);
        }

        if (isset($search->targetShippingZone) and Validate::not_null($search->targetShippingZone)) {
            $statement->andWhere('szp.targetShippingZone = :targetShippingZone');
            $statement->setParameter('targetShippingZone', $search->targetShippingZone);
        }

        if (isset($search->shippingTime) and Validate::not_null($search->shippingTime)) {
            $statement->andWhere('szp.shippingTime = :shippingTime');
            $statement->setParameter('shippingTime', $search->shippingTime);
        }

        if (isset($search->hasRates) and Validate::not_null($search->hasRates)) {
            $statement->andWhere('szp.hasRates = :hasRates');
            $statement->setParameter('hasRates', $search->hasRates);
        }
        if (isset($search->currency) and Validate::not_null($search->currency)) {
            $statement->andWhere('szp.currency = :currency');
            $statement->setParameter('currency', $search->currency);
        }

        if (isset($search->courier) and Validate::not_null($search->courier)) {
            $statement->andWhere('szp.courier = :courier');
            $statement->setParameter('courier', $search->courier);
        }
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('szp.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
