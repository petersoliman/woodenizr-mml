<?php

namespace App\OnlinePaymentBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\OnlinePaymentBundle\Entity\PaymentMethod;
use PN\ServiceBundle\Utils\Validate;

class PaymentMethodRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class, "pm");
    }


    public function findAll(): array
    {
        return $this->findBy(['deleted' => null]);
    }

    public function findOneByActiveType(PaymentMethodEnum $type): ?PaymentMethod
    {
        return $this->findOneBy(["type" => $type, 'deleted' => null, 'active' => true]);
    }

    public function findByActiveTypes(array $type): array
    {
        return $this->findBy(["type" => $type, 'deleted' => null, 'active' => true]);
    }

    public function findByActive(): array
    {
        return $this->findBy(['deleted' => null, 'active' => true]);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('pm');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search)
    {
        $sortSQL = [
            'pm.title',
            'pm.fees',
            'pm.active',
            'pm.modified',
        ];

        if (isset($search->ordr) and Validate::not_null($search->ordr)) {
            $dir = $search->ordr['dir'];
            $columnNumber = $search->ordr['column'];
            if (isset($columnNumber) and array_key_exists($columnNumber, $sortSQL)) {
                $statement->addOrderBy($sortSQL[$columnNumber], $dir);
            }
        } else {
            $statement->addOrderBy($sortSQL[1]);
        }
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('pm.id LIKE :searchTerm '
                .'OR pm.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('pm.deleted IS NOT NULL');
            } else {
                $statement->andWhere('pm.deleted IS NULL');
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

        $statement->groupBy('pm.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
