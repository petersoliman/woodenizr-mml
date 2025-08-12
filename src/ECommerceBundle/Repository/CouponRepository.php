<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\Coupon;
use App\ProductBundle\Entity\Product;
use App\UserBundle\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends BaseRepository<Coupon>
 *
 * @method Coupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method Coupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method Coupon[]    findAll()
 * @method Coupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouponRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class, "c");
    }

    /**
     * Check if coupon has Product
     *
     * @param Coupon $coupon
     * @param Product $product
     * @return (bool)
     * @throws Exception
     */
    public function checkCouponHasProduct(Coupon $coupon, Product $product): bool
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "SELECT product_id FROM coupon_has_product WHERE product_id = :productId AND coupon_id = :couponId";

        $statement = $connection->prepare($sql);
        $statement->bindValue("couponId", $coupon->getId());
        $statement->bindValue("productId", $product->getId());
        $queryResult = $statement->executeQuery()->fetchOne();

        return !!$queryResult;
    }

    public function removeCouponHasProduct(Coupon $coupon, Product $product): void
    {
        $sql = " DELETE FROM coupon_has_product WHERE coupon_id=? AND product_id=?";
        $this->getEntityManager()->getConnection()
            ->executeQuery($sql, array($coupon->getId(), $product->getId()));
    }

    /**
     * Check coupon number user by user
     *
     * @param Coupon $coupon
     * @param User $user
     * @return int|null
     */
    public function couponUsedCountByUser(Coupon $coupon, UserInterface $user): ?int
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "SELECT count(o.id) FROM `order` o "
            . " RIGHT OUTER JOIN order_has_coupon ohc ON ohc.order_id=o.id "
            . " WHERE o.user_id=:userId and ohc.coupon_id=:couponId";

        $statement = $connection->prepare($sql);
        $statement->bindValue("userId", $user->getId());
        $statement->bindValue("couponId", $coupon->getId());
        $queryResult = $statement->executeQuery()->fetchOne();
        if (!$queryResult) {
            return null;
        }

        return (int)$queryResult;
    }

    /**
     * get product between cart and coupon to validate if this coupon apply on this cart or not
     *
     * @param Coupon $coupon
     * @param Cart $cart
     * @param bool $count
     * @return array|int
     * @throws Exception
     */
    public function getRelatedProductsBetweenCartAndCoupon(Coupon $coupon, Cart $cart, bool $count = true): array|int
    {
        $connection = $this->getEntityManager()->getConnection();
        if ($count === true) {
            $selectColumn = 'COUNT(*) count';
        } else {
            $selectColumn = 'cart_products.id';
        }
        $sql = "SELECT $selectColumn FROM ( "
            . "SELECT p.id FROM coupon c  "
            . "LEFT OUTER JOIN coupon_has_product chp ON chp.coupon_id=c.id "
            . "LEFT OUTER JOIN product p ON chp.product_id=p.id "
            . "WHERE c.id = :couponId AND p.deleted IS NULL "
            . ") coupon_products INNER JOIN  ("
            . "SELECT p.id FROM cart_has_product_price chpp  "
            . "LEFT OUTER JOIN product_price pp ON pp.id=chpp.product_price_id "
            . "LEFT OUTER JOIN product p ON pp.product_id=p.id  "
            . "WHERE chpp.cart_id=:cartId AND p.deleted IS NULL AND pp.deleted IS NULL"
            . ") cart_products ON coupon_products.id =cart_products.id "
            . "GROUP BY cart_products.id";

        $statement = $connection->prepare($sql);
        $statement->bindValue("couponId", $coupon->getId());
        $statement->bindValue("cartId", $cart->getId());
        if ($count === true) {
            $queryResult = $statement->executeQuery()->fetchOne();
            return (int)$queryResult;
        } else {
            $queryResult = $statement->executeQuery()->fetchAllAssociative();
            $result = [];
            foreach ($queryResult as $value) {
                $result[] = $value['id'];
            }

            return $result;
        }
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('c');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'c.id',
            'c.code',
            'c.discountValue',
            "c.startDate",
            "c.expiryDate",
            "c.active",
            'c.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('c.code LIKE :searchTerm ');
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('c.deleted IS NOT NULL');
            } else {
                $statement->andWhere('c.deleted IS NULL');
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

        $statement->groupBy('c.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
