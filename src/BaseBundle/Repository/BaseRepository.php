<?php

namespace App\BaseBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

abstract class BaseRepository extends ServiceEntityRepository
{
    protected string $tableAlias;

    public function __construct(ManagerRegistry $registry, string $entityClass, string $tableAlias)
    {
        $this->tableAlias = $tableAlias;
        parent::__construct($registry, $entityClass);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        if ($this->getTableIdentifierColumnName() === null) {
            throw ORMInvalidArgumentException::invalidCompositeIdentifier();
        }

        return $this->findOneBy([$this->getTableIdentifierColumnName() => $id]);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?object
    {
        if (!method_exists($this, "getStatement")) {
            return parent::findOneBy($criteria, $orderBy);
        }
        $statement = $this->getBuilder($criteria, $orderBy);
        $statement->setMaxResults(1);

        $row = $statement->getQuery()->getOneOrNullResult();


        return $this->convertFindAndFindByResultToObject($row);
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        //            return parent::findBy($criteria, $orderBy, $limit, $offset);
        if (!method_exists($this, "getStatement")) {
            return parent::findBy($criteria, $orderBy, $limit, $offset);
        }
        $statement = $this->getBuilder($criteria, $orderBy);

        if ($limit !== null) {
            $statement->setMaxResults($limit);
        }
        if ($offset !== null) {
            $statement->setFirstResult($offset);
        }
        $rows = $statement->getQuery()->execute();
        $return = [];
        foreach ($rows as $row) {
            $return[] = $this->convertFindAndFindByResultToObject($row);
        }

        return $return;

    }

    protected function filterPagination(QueryBuilder $statement, $startLimit = null, $endLimit = null, $groupById = false): void
    {
        if ($startLimit !== null and $endLimit !== null) {
            $statement->setFirstResult($startLimit)
                ->setMaxResults($endLimit);
            if ($groupById == true) $statement->groupBy("{$this->tableAlias}.id");
        }
    }

    protected function filterCount(QueryBuilder $statement, $columnName = "id"): int
    {
        $statement->select("COUNT(DISTINCT {$this->tableAlias}.{$columnName})");
        $statement->setMaxResults(1);

        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return (int)reset($count);
        }

        return 0;
    }

    protected function filterOrderLogic(
        QueryBuilder $statement,
        \stdClass    $search,
        array        $sortSQL,
                     $defaultSortNumber = 0
    ): void
    {
        if (isset($search->ordr) and Validate::not_null($search->ordr)) {
            $dir = $search->ordr['dir'];
            $columnNumber = $search->ordr['column'];
            if (isset($columnNumber) and array_key_exists($columnNumber, $sortSQL)) {
                $statement->addOrderBy($sortSQL[$columnNumber], $dir);
            }
        } else {
            $statement->addOrderBy($sortSQL[$defaultSortNumber]);
        }
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return QueryBuilder
     */
    private function getBuilder(array $criteria, ?array $orderBy): QueryBuilder
    {
        $statement = $this->getStatement(new \stdClass());
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $exp = $statement->expr()->in("{$this->tableAlias}.{$key}", ":{$key}");
                $statement->andWhere($exp);
                $statement->setParameter($key, $value);
            } elseif ($value === null) {
                $statement->andWhere("{$this->tableAlias}.{$key} IS NULL");
            } else {
                $exp = $statement->expr()->eq("{$this->tableAlias}.{$key}", ":{$key}");
                $statement->andWhere($exp);
                $statement->setParameter($key, $value);
            }

        }

        foreach ($orderBy ?: [] as $sort => $order) {
            $statement->orderBy("{$this->tableAlias}.{$sort}", $order);
        }
        if ($this->getTableIdentifierColumnName() !== null) {
            $statement->groupBy("{$this->tableAlias}." . $this->getTableIdentifierColumnName());
        }

        return $statement;
    }

    private function getTableIdentifierColumnName(): ?string
    {
        $metadata = $this->getClassMetadata();
        if ($metadata->isIdentifierComposite) {
            return null;
        }

        return $metadata->identifier[0];
    }

    private function convertFindAndFindByResultToObject(object|array|null $row): ?object
    {
        if ($row === null) {
            return null;
        }
        if (is_array($row)) {
            $object = $row[0];
            if ($object == null) {
                return null;
            }
            foreach ($row as $key => $item) {
                if (!is_numeric($key)) {
                    $object->{$key} = $item;
                }
            }
        } else {
            $object = $row;
        }

        return $object;
    }
}