<?php

namespace App\NewShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\NewShippingBundle\Entity\ShippingZone;
use App\NewShippingBundle\Entity\Zone;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method ShippingZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingZone[]    findAll()
 * @method ShippingZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingZoneRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingZone::class,"sz");
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return $this->findOneBy(array('id' => $id, 'deleted' => null));
    }

    public function findOneByShippingZoneIdAndParentZone($id): ?ShippingZone
    {
        $statement = $this->getStatement()
            ->andWhere('sz.deleted IS NULL')
            ->andWhere('sz.id = :shippingZoneId')
            ->setParameter("shippingZoneId", $id)
            ->setMaxResults(1);

        return $statement->getQuery()->getOneOrNullResult();
    }


    public function getOneZone(Zone $zone): ?ShippingZone
    {
        return $this->createQueryBuilder('sz')
            ->leftJoin('sz.zones', 'szz')
            ->andWhere('sz.deleted IS NULL')
            ->andWhere('szz.deleted IS NULL')
            ->andWhere('szz.id = :zoneId')
            ->setParameter("zoneId", $zone->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('sz')
            ->leftJoin('sz.zones', 'szs');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'sz.id',
            'sz.title',
            'sz.created',
            'sz.creator',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('sz.id LIKE :searchTerm '
                .'OR sz.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->deleted) and in_array($search->deleted, array(0, 1))) {
            if ($search->deleted == 1) {
                $statement->andWhere('sz.deleted IS NOT NULL');
            } else {
                $statement->andWhere('sz.deleted IS NULL');
            }
        }
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('sz.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
