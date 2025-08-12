<?php

namespace App\CustomBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CustomBundle\Entity\MaterialCategory;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<MaterialCategory>
 *
 * @method MaterialCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaterialCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaterialCategory[]    findAll()
 * @method MaterialCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialCategoryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialCategory::class, "mc");
    }
    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('mc')
            ->addSelect("-mc.tarteb AS HIDDEN inverseTarteb")
            ->addSelect("mcTrans")
            ->leftJoin("mc.translations", "mcTrans");
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('mc.id LIKE :searchTerm '
                .'OR mc.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('mc.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }

        if (isset($search->notId) and $search->notId != "") {
            $statement->andWhere('mc.id <> :notId');
            $statement->setParameter('notId', $search->notId);
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('mc.deleted IS NOT NULL');
            } else {
                $statement->andWhere('mc.deleted IS NULL');
            }
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'mc.id',
            'inverseTarteb',
            'mc.title',
            'mc.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }
    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('mc.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
