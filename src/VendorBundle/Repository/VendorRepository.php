<?php

namespace App\VendorBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\VendorBundle\Entity\Vendor;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

class VendorRepository extends BaseRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vendor::class, "v");
    }

    public function findAll(): array
    {
        return $this->findBy(['deleted' => null]);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('v');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'v.id',
            'v.title',
            'v.commissionPercentage',
            'v.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement
                ->andWhere('v.id LIKE :searchTerm '
                    . 'OR v.title LIKE :searchTerm '
                )
                ->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('v.deleted IS NOT NULL');
            } else {
                $statement->andWhere('v.deleted IS NULL');
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

        $statement->groupBy('v.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
