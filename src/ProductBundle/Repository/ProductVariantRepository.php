<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductVariant;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<ProductVariant>
 *
 * @method ProductVariant|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductVariant|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductVariant[]    findAll()
 * @method ProductVariant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductVariantRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class, "pv");
    }


    public function findByProduct(Product $product)
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->ordr = ["column" => 0, "dir" => "ASC"];
        $search->product = $product->getId();

        return $this->filter($search);
    }


    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('pv')
            ->addSelect("-pv.tarteb AS HIDDEN inverseTarteb")
//            ->addSelect("o")
//            ->addSelect("i")
            ->leftJoin("pv.options", "o")
            ->leftJoin("o.image", "i")
            ;
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "pv.id",
            "inverseTarteb",
            "pv.title",
            "pv.type",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('pv.id LIKE :searchTerm '
                . 'OR pv.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('pv.id = :id');
            $statement->setParameter('id', $search->id);
        }
        if (isset($search->notId) and $search->notId != "") {
            $statement->andWhere('pv.id <> :id');
            $statement->setParameter('id', $search->notId);
        }
        if (isset($search->notIds) and is_array($search->notIds) and count($search->notIds) > 0) {
            $statement->andWhere('pv.id NOT IN (:notIds)');
            $statement->setParameter('notIds', $search->notIds);
        }

        if (isset($search->product) and $search->product > 0) {
            $statement->andWhere('pv.product = :product');
            $statement->setParameter('product', $search->product);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('pv.deleted IS NOT NULL');
            } else {
                $statement->andWhere('pv.deleted IS NULL');
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


        $this->filterPagination($statement, $startLimit, $endLimit, true);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
