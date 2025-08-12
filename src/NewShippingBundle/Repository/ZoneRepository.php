<?php

namespace App\NewShippingBundle\Repository;

use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\ShippingZonePrice;
use App\NewShippingBundle\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method Zone|null find($id, $lockMode = null, $lockVersion = null)
 * @method Zone|null findOneBy(array $criteria, array $orderBy = null)
 * @method Zone[]    findAll()
 * @method Zone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function getUnusedZonesInShippingZones(ShippingZone $shippingZone = null)
    {
        $statement = $this->createQueryBuilder("z")
            ->leftJoin("z.shippingZones", "sz", "WITH", "sz.deleted IS NULL")
            ->andWhere("z.deleted IS NULL");
        //            ->andWhere("sz.deleted IS NULL")
        if ($shippingZone == null) {
            $statement->andWhere("sz.id IS NULL");
        } else {
            $statement->andWhere("sz.id IS NULL OR sz.id = :shippingZoneId")
                ->setParameter("shippingZoneId", $shippingZone->getId());
        }


        return $statement->getQuery()
            ->getResult();
    }

    /**
     * batgeb kol el zones el na2der nash7en leha
     * @return array
     */
    public function getZonesReadyToShipping(Zone $rootZone = null): Zone|array
    {
        $statement = $this->createQueryBuilder("z")
            ->addSelect("-z.tarteb AS HIDDEN inverseTarteb")
            ->addSelect('translations')
            ->innerJoin("z.shippingZones", "sz")
            ->leftJoin(ShippingZonePrice::class, 'szp', "WITH", "szp.targetShippingZone=sz.id")
            ->leftJoin('z.translations', 'translations')
            ->andWhere("z.deleted IS NULL")
            ->andWhere("sz.deleted IS NULL")
            ->andWhere("sz.id IS NOT NULL")
            ->andWhere("szp.hasRates = 1");
        if ($rootZone != null) {
            $statement->andWhere("z.id = :rootZoneId")
                ->setParameter("rootZoneId", $rootZone->getId());
            return $statement->getQuery()->getOneOrNullResult();

        }
        $statement->orderBy("inverseTarteb", "DESC");

        return $statement->getQuery()->getResult();
    }


    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->addSelect("-t.tarteb AS HIDDEN inverseTarteb");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search)
    {
        $sortSQL = [
            'inverseTarteb',
            't.title',
            't.created',
            't.creator',
        ];

        if (isset($search->ordr) and Validate::not_null($search->ordr)) {
            $dir = $search->ordr['dir'];
            $columnNumber = $search->ordr['column'];
            if (isset($columnNumber) and array_key_exists($columnNumber, $sortSQL)) {
                $statement->addOrderBy($sortSQL[$columnNumber], $dir);
            }
        } else {
            $statement->addOrderBy($sortSQL[0]);
        }
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('t.id LIKE :searchTerm '
                . 'OR t.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->deleted) and in_array($search->deleted, array(0, 1))) {
            if ($search->deleted == 1) {
                $statement->andWhere('t.deleted IS NOT NULL');
            } else {
                $statement->andWhere('t.deleted IS NULL');
            }
        }
    }

    private function filterPagination(QueryBuilder $statement, int $startLimit = null, int $endLimit = null): void
    {
        if ($startLimit !== null or $endLimit !== null) {
            $statement->setFirstResult($startLimit)
                ->setMaxResults($endLimit);
        }
    }

    private function filterCount(QueryBuilder $statement): int
    {
        $statement->select("COUNT(DISTINCT t.id)");
        $statement->setMaxResults(1);

        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return (int)reset($count);
        }

        return 0;
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
