<?php

namespace App\ReportBundle\Repository;

use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ECommerceBundle\Entity\OrderHasProductPrice;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use Doctrine\ORM\QueryBuilder;
use PN\ServiceBundle\Utils\Validate;

class ProductSalesRepository extends GenericRepository
{

    public function bestsellers(): array
    {
        $result = $this->createQueryBuilder()
            ->select("ohpp AS orderHasProductPrice")
            ->addSelect("pp, p")
            ->addSelect("SUM(ohpp.qty) totalQty")
            ->addSelect("SUM(ohpp.totalPrice) totalSales")
            ->from(OrderHasProductPrice::class, "ohpp")
            ->leftJoin("ohpp.order", "o")
            ->leftJoin("ohpp.productPrice", "pp")
            ->leftJoin("pp.product", "p")
            ->andWhere("o.state = :state")
            ->setParameter("state", OrderStatusEnum::SUCCESS->value)
            ->groupBy("p.id")
            ->orderBy("totalQty", "DESC")
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $return = [];
        foreach ($result as $row) {
            $product = $row['orderHasProductPrice']->getProductPrice()->getProduct();
            $product->totalQty = $row['totalQty'];
            $product->totalSales = $row['totalSales'];
            $return[] = $product;
        }

        return $return;
    }

    public function topProductsInCart(): array
    {
        $result = $this->createQueryBuilder()
            ->select("p.id")
            ->addSelect("chpp AS cartHasProductPrice")
            ->addSelect("pp, p")
            ->addSelect("SUM(chpp.qty) AS count")
            ->from(CartHasProductPrice::class, "chpp")
            ->leftJoin("chpp.productPrice", "pp")
            ->leftJoin("pp.product", "p")
            ->andWhere("p.deleted IS NULL")
            ->andWhere("pp.deleted IS NULL")
            ->groupBy("p.id")
            ->orderBy("count", "DESC")
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        $return = [];
        foreach ($result as $row) {
            $product = $row['cartHasProductPrice']->getProductPrice()->getProduct();
            $product->count = $row['count'];
            $return[] = $product;
        }

        return $return;
    }

    public function getSoldProductAndTotalSales(): \stdClass
    {
        $sql = "SELECT COUNT(x.id) totalProducts, SUM(total) totalSales FROM ("
            ."SELECT p.id, SUM(ohpp.total_price) total FROM `order` o "
            ."LEFT JOIN order_has_product_price ohpp ON o.id= ohpp.order_id "
            ."LEFT JOIN product_price pp ON pp.id=ohpp.product_price_id "
            ."LEFT JOIN product p ON p.id=pp.product_id WHERE o.state=:state "
            ."GROUP BY p.id) x";
        $statement = $this->getConnection()->prepare($sql);
        $statement->bindValue("state", OrderStatusEnum::SUCCESS->value);
        $filterResult = $statement->executeQuery()->fetchAssociative();

        $result = new \stdClass();
        $result->totalProducts = $filterResult['totalProducts'];
        $result->totalSales = $filterResult['totalSales'];

        return $result;
    }

    public function getTotalProductsInCart(): int
    {
        $sql = "SELECT SUM(chpp.qty) `count` FROM cart_has_product_price chpp "
            ."LEFT JOIN product_price pp ON chpp.product_price_id=pp.id "
            ."LEFT JOIN product p ON pp.product_id=p.id "
            ."WHERE p.deleted IS NULL AND pp.deleted IS NULL";
        $statement = $this->getConnection()->prepare($sql);
        $result = $statement->executeQuery()->fetchOne();

        return ($result !== null) ? $result : 0;
    }

    public function getTotalQtyAndTotalSales($month, $year): \stdClass
    {
        $result = $this->createQueryBuilder()
            ->addSelect("SUM(ohpp.qty) totalQty")
            ->addSelect("SUM(ohpp.totalPrice) totalSales")
            ->from(OrderHasProductPrice::class, "ohpp")
            ->leftJoin("ohpp.order", "o")
            ->leftJoin("ohpp.productPrice", "pp")
            ->leftJoin("pp.product", "p")
            ->andWhere("o.state = :state")
            ->andWhere("MONTH(o.created) = :month")
            ->andWhere("YEAR(o.created) = :year")
            ->setParameter("state", OrderStatusEnum::SUCCESS->value)
            ->setParameter("month", $month)
            ->setParameter("year", $year)
            ->getQuery()
            ->getSingleResult();

        $return = new \stdClass();
        $return->totalQty = $result['totalQty'];
        $return->totalSales = $result['totalSales'];

        return $return;
    }

    public function productSalesFilter(
        \stdClass $search,
        bool $count = false,
        ?int $startLimit = null,
        ?int $endLimit = null,
        bool $totalSales = false,
        bool $totalQty = false,
    ): int|array|\stdClass {
        $statement = $this->productSalesFilterStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        } elseif ($totalSales and !$totalQty) {
            $result = $statement->getQuery()->getOneOrNullResult();
            if ($result != null and array_key_exists("totalSales", $result)) {
                return $result['totalSales'];
            }
        } elseif ($totalQty and !$totalSales) {
            $result = $statement->getQuery()->getOneOrNullResult();
            if ($result != null and array_key_exists("totalQty", $result)) {
                return $result['totalQty'];
            }
        } elseif ($totalQty and $totalSales) {
            $result = $statement->getQuery()->getOneOrNullResult();
            if ($result != null and array_key_exists("totalSales", $result) and array_key_exists("totalQty", $result)) {
                $return = new \stdClass();
                $return->totalQty = $result['totalQty'];
                $return->totalSales = $result['totalSales'];

                return $return;
            }
        }
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);
        $statement->groupBy("p.id");
        $rows = $statement->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $object = $row['orderHasProductPrice']->getProductPrice()->getProduct();
            $object->totalQty = $row['totalQty'];
            $object->totalSales = $row['totalSales'];
            $object->totalNoOfOrders = $row['totalNoOfOrders'];
            $object->lastTimePurchased = ($row['lastTimePurchased'] != null) ? new \DateTime($row['lastTimePurchased']) : null;
            $result[] = $object;
        }

        return $result;

    }

    private function productSalesFilterStatement(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select("ohpp AS orderHasProductPrice")
            ->addSelect("pp, p")
            ->addSelect("IFNULL(SUM(ohpp.qty), 0) totalQty")
            ->addSelect("IFNULL(SUM(ohpp.totalPrice), 0) totalSales")
            ->addSelect("COUNT(DISTINCT o.id) totalNoOfOrders")
            ->addSelect("MAX(o.created) lastTimePurchased")
            ->from(OrderHasProductPrice::class, "ohpp")
            ->leftJoin("ohpp.order", "o")
            ->leftJoin("ohpp.productPrice", "pp")
            ->leftJoin("pp.product", "p")
            ->andWhere("o.state = :state")
            ->setParameter("state", OrderStatusEnum::SUCCESS->value);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->productId) and Validate::not_null($search->productId)) {
            $statement->andWhere('p.id = :productId');
            $statement->setParameter('productId', $search->productId);
        }
        if (isset($search->productName) and Validate::not_null($search->productName)) {
            $statement->andWhere('p.title LIKE :searchTerm ');
            $statement->setParameter('searchTerm', '%'.trim($search->productName).'%');
        }

        if (isset($search->featured) and is_bool($search->featured)) {
            $statement->andWhere('p.featured = :featured');
            $statement->setParameter('featured', $search->featured);
        }
        if (isset($search->orderId) and Validate::not_null($search->orderId)) {
            $statement->andWhere('o.id = :orderId');
            $statement->setParameter('orderId', $search->orderId);
        }
        if (isset($search->publish) and is_bool($search->publish)) {
            $statement->andWhere('p.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }

        if (isset($search->startDate) and $search->startDate instanceof \DateTimeInterface) {
            $statement->andWhere("o.created >= STR_TO_DATE(:startDate, '%d/%m/%Y')");
            $statement->setParameter('startDate', $search->startDate->format("d/m/Y"));
        }

        if (isset($search->endDate) and $search->endDate  instanceof \DateTimeInterface) {
            $statement->andWhere("o.created <=  DATE_ADD(STR_TO_DATE(:endDate, '%d/%m/%Y') , 1, 'DAY')");
            $statement->setParameter('endDate', $search->endDate->format("d/m/Y"));
        }
        if (isset($search->month) and Validate::not_null($search->month)) {
            $statement->andWhere("MONTH(o.created) = :month");
            $statement->setParameter("month", $search->month);
        }
        if (isset($search->year) and Validate::not_null($search->year)) {
            $statement->andWhere("YEAR(o.created) = :year");
            $statement->setParameter("year", $search->year);

        }

    }

    private function filterCount(QueryBuilder $statement): int
    {
        $statement->select("COUNT(ohpp.productPrice)");
        $statement->setMaxResults(1);

        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return (int)reset($count);
        }

        return 0;
    }

    private function filterPagination(QueryBuilder $statement, $startLimit = null, $endLimit = null): void
    {
        if ($startLimit !== null and $endLimit !== null) {
            $statement->setFirstResult($startLimit)
                ->setMaxResults($endLimit);
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            1 => "totalQty",
            2 => 'totalSales',
        ];
        if (isset($search->ordr) and Validate::not_null($search->ordr) and array_key_exists($search->ordr, $sortSQL)) {
            $statement->addOrderBy($sortSQL[$search->ordr], "DESC");
        }

        $statement->addOrderBy("p.title", "ASC");
    }
}