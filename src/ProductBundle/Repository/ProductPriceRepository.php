<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method ProductPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductPrice[]    findAll()
 * @method ProductPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductPriceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPrice::class, "pp");
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('pp');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "pp.id",
            "pp.product",
            "pp.unitPrice",
            "pp.promotionalPrice",
            "pp.promotionalExpiryDate",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('pp.id LIKE :searchTerm '
                . 'OR pp.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('pp.id = :id');
            $statement->setParameter('id', $search->id);
        }

        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('pp.id IN (:ids)');
            $statement->setParameter('ids', $search->ids);
        }

        if (isset($search->product) and $search->product > 0) {
            $statement->andWhere('pp.product = :product');
            $statement->setParameter('product', $search->product);
        }

        if (isset($search->hasPrice) and $search->hasPrice === true) {
            $statement->andWhere('pp.unitPrice > 0');
        }
        if (isset($search->hasStock) and $search->hasStock === true) {
            $statement->andWhere('pp.stock > 0');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('pp.deleted IS NOT NULL');
            } else {
                $statement->andWhere('pp.deleted IS NULL');
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

    public function getMaxAndMinPrices()
    {
        $statement = $this->createQueryBuilder('pp')
            ->select('COALESCE(MAX(pp.promotionalPrice), MAX(pp.unitPrice)) AS max_price, COALESCE(MIN(pp.promotionalPrice), MIN(pp.unitPrice)) AS min_price')
            ->where('pp.product IS NOT NULL')
            ->andWhere('pp.deleted IS NULL');

        $result = $statement->getQuery()->execute();

        return $result ? $result[0] : null;
    }


    public function getMinPrice(Product $product): ?ProductPrice
    {
        return $this->createQueryBuilder('pp')
            ->addSelect("CASE WHEN(DATEDIFF(pp.promotionalExpiryDate, CURRENT_DATE()) >= 0) THEN pp.promotionalPrice ELSE pp.unitPrice END AS HIDDEN minPrice")
            ->andWhere("pp.deleted IS NULL")
            ->andWhere("pp.product = :productId")
            ->andWhere("pp.unitPrice > 0")
            ->setParameter("productId", $product->getId())
            ->orderBy("minPrice", "ASC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
