<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductFavorite;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method ProductFavorite|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductFavorite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductFavoriteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductFavorite::class, "pf");
    }

    protected function getStatement($search): QueryBuilder
    {
        $statement = $this->createQueryBuilder('pf')
            ->leftJoin("pf.product", 'p');

        if (isset($search->currentUserId) and Validate::not_null($search->currentUserId)) {
            $statement->addSelect('p');
            $statement->leftJoin('p.favorites', 'pff', "WITH", "pff.user=:currentUserId");
            $statement->addSelect("IDENTITY(pff.product) hasFavorite");
            $statement->setParameter("currentUserId", $search->currentUserId);
        }

        return $statement;
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->user) and $search->user != "") {
            $statement->andWhere('pf.user = :user');
            $statement->setParameter('user', $search->user);
        }

        if (isset($search->product) and $search->product != "") {
            $statement->andWhere('pf.product = :product');
            $statement->setParameter('product', $search->product);
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'pf.user',
            'pf.product',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null): array|int
    {
        $statement = $this->getStatement($search);
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement, "product");
        }

        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        $statementResult = $statement->getQuery()->execute();

        $products = [];
        if (is_array($statementResult)) {
            foreach ($statementResult as $row) {
                if (is_array($row)) {

                    $productFavorite = $row['0'];
                    $product = $productFavorite->getProduct();
                    $product->hasFavorite = (array_key_exists("hasFavorite",
                            $row) and $row['hasFavorite'] != null) ? true : false;
                    $product->minPrice = $this->getEntityManager()->getRepository(Product::class)->getMinPrice($product);
                } else {
                    $product = $row->getProduct();
                }
                $products[] = $product;
            }
        }

        return $products;

    }

}

