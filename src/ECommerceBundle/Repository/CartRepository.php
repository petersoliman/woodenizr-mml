<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\Cart;
use App\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Cart>
 *
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class, "c");
    }
    public function remove(Cart $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    public function removeAllCookieCartByDays(int $days = 3): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "DELETE `c` FROM cart_has_cookie chc "
            . "LEFT JOIN cart c ON c.id=chc.cart_id "
            . "WHERE TIMESTAMPDIFF(DAY, c.created, NOW()) >= :days";
        $statement = $connection->prepare($sql);
        $statement->bindValue("days", $days);
        $statement->executeQuery();
    }

    public function removeAllUserCartByDays(int $days = 20): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "DELETE `c` FROM cart_has_user chu "
            . "LEFT JOIN cart c ON c.id=chu.cart_id "
            . "WHERE TIMESTAMPDIFF(DAY, c.created, NOW()) >= :days";
        $statement = $connection->prepare($sql);
        $statement->bindValue("days", $days);
        $statement->executeQuery();
    }


    public function getLastUpdateDate(Cart $cart)
    {
        $result = $this->createQueryBuilder("c")
            ->select("GREATEST(COALESCE(MAX(chpp.created),0), c.created) AS created")
            ->leftJoin("c.cartHasProductPrices", "chpp")
            ->setMaxResults(1)
            ->andWhere("c.id = :cartId")
            ->setParameter("cartId", $cart->getId())
            ->getQuery()
            ->getOneOrNullResult();

        return $result === null ? null : $result["created"];
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder("c")
            ->addSelect("chu")
            ->addSelect("chc")
            ->addSelect("cgd")
            ->addSelect("chpp")
            ->addSelect("st")
            ->addSelect("sa")
            ->addSelect("cou")
            ->addSelect("pm")
            ->leftJoin("c.cartHasUser", "chu")
            ->leftJoin("c.cartHasCookie", "chc")
            ->leftJoin("c.cartGuestData", "cgd")
            ->leftJoin("c.cartHasProductPrices", "chpp")
            ->leftJoin("chpp.shippingTime", "st")
            ->leftJoin("c.shippingAddress", "sa")
            ->leftJoin("c.coupon", "cou")
            ->leftJoin("c.paymentMethod", "pm");
    }

    public function getCartByUser(User $user): ?Cart
    {
        return $this->getStatement()
            ->andWhere("chu.user = :userId")
            ->setParameter("userId", $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCartByCookie(string $cookieHash): ?Cart
    {
        return $this->getStatement()
            ->andWhere("chc.cookie = :cookieHash")
            ->setParameter("cookieHash", $cookieHash)
            ->addOrderBy("c.id", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
