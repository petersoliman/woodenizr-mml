<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\ProductBundle\Entity\SubAttribute;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method SubAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubAttribute[]    findAll()
 * @method SubAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubAttributeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubAttribute::class, "sa");
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('sa');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "sa.id",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('sa.id LIKE :searchTerm '
                .'OR sa.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('sa.id = :id');
            $statement->setParameter('id', $search->id);
        }

        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('sa.id IN (:ids)');
            $statement->setParameter('ids', $search->ids);
        }
        if (isset($search->attribute) and $search->attribute > 0) {
            $statement->andWhere('sa.attribute = :attribute');
            $statement->setParameter('attribute', $search->attribute);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('sa.deleted IS NOT NULL');
            } else {
                $statement->andWhere('sa.deleted IS NULL');
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

        $statement->groupBy('sa.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
