<?php

namespace App\ShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ShippingBundle\Entity\City;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<City>
 *
 * @method City|null find($id, $lockMode = null, $lockVersion = null)
 * @method City|null findOneBy(array $criteria, array $orderBy = null)
 * @method City[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CityRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class, "c");
    }

    public function findAll(): array
    {
        return $this->findBy(['deleted' => false]);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('c');
    }

    public function getZoneReadyToShippingById(int $zoneId): ?City
    {
        return $this->getStatement()
            ->andWhere('c.deleted IS NULL')
            ->andWhere('c.publish = 1')
            ->andWhere("c.id = :id")
            ->setParameter("id", $zoneId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getZonesReadyToShipping(): array
    {
        return $this->getStatement()
            ->andWhere('c.deleted IS NULL')
            ->andWhere('c.publish = 1')
            ->getQuery()
            ->getResult();
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'c.id',
            'c.title',
            'c.price',
            "c.publish",
            'c.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('c.title LIKE :searchTerm ');
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('c.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('c.deleted IS NOT NULL');
            } else {
                $statement->andWhere('c.deleted IS NULL');
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

        $statement->groupBy('c.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
