<?php

namespace App\VendorBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\VendorBundle\Entity\StoreAddress;
use PN\ServiceBundle\Utils\Validate;

class StoreAddressRepository extends BaseRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreAddress::class, "sa");
    }

    public function findAll(): array
    {
        return $this->findBy(['deleted' => null]);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('sa')
//            ->leftJoin('sa.zone', 'z')
            ;
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'sa.id',
            'sa.title',
            'sa.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);

    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('sa.fullAddress LIKE :searchTerm '
                .'OR sa.fullAddress LIKE :searchTerm '
                .'OR z.title LIKE :searchTerm '
            )
                ->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('sa.deleted IS NOT NULL');
            } else {
                $statement->andWhere('sa.deleted IS NULL');
            }
        }

        if (isset($search->vendor) and Validate::not_null($search->vendor)) {
            $statement->andWhere('sa.vendor = :vendor')
                ->setParameter('vendor', $search->vendor);
        }

    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count == true) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('sa.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
