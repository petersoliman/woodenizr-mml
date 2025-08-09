<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\Brand;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<Brand>
 *
 * @method Brand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Brand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Brand[]    findAll()
 * @method Brand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrandRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class, "b");
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->addSelect("t, l")
            ->addSelect("-b.tarteb AS HIDDEN inverseTarteb")
            ->leftJoin("b.translations", "t")
            ->leftJoin("t.language", "l");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "b.id",
            "b.title",
            "b.publish",
            "b.featured",
            "b.created",
            "inverseTarteb",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('b.id LIKE :searchTerm '
                .'OR b.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('b.id = :id');
            $statement->setParameter('id', $search->id);
        }
        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('b.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }

        if (isset($search->featured) and (is_bool($search->featured) or in_array($search->featured, [0, 1]))) {
            $statement->andWhere('b.featured = :featured');
            $statement->setParameter('featured', $search->featured);
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('b.deleted IS NOT NULL');
            } else {
                $statement->andWhere('b.deleted IS NULL');
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

        $statement->groupBy('b.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
