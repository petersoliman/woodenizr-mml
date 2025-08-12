<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\Cart;
use App\ECommerceBundle\Entity\CartHasProductPrice;
use App\ProductBundle\Entity\Product;
use App\ProductBundle\Entity\ProductPrice;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CartHasProductPrice>
 *
 * @method CartHasProductPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartHasProductPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartHasProductPrice[]    findAll()
 * @method CartHasProductPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartHasProductPriceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartHasProductPrice::class, "chpp");
    }

    /**
     * get valid Product price in cart
     * @param Cart $cart
     * @return array
     */
    public function getCartValidProductPriceByCart(Cart $cart): array
    {
        return $this->createQueryBuilder("chpp")
            ->addSelect("pp")
            ->addSelect("p")
            ->addSelect("c")
            ->leftJoin("chpp.cart", "c")
            ->leftJoin("chpp.productPrice", "pp")
            ->leftJoin("pp.product", "p")
            ->andWhere("chpp.cart = :cartId")
            ->andWhere("p.publish = 1")
            ->andWhere("pp.deleted IS NULL")
            ->andWhere("p.deleted IS NULL")
            ->setParameter("cartId", $cart->getId())
            ->getQuery()
            ->getResult();
    }

    public function deleteByProduct(Product $product): void
    {
        $ids = $this->createQueryBuilder("chpp")
            ->select("IDENTITY(chpp.productPrice)")
            ->leftJoin("chpp.productPrice", "pp")
            ->andWhere("pp.product = :productId")
            ->setParameter("productId", $product->getId())
            ->distinct()
            ->getQuery()
            ->getSingleColumnResult();
        if (count($ids)) {
            $this->deleteByProductPriceIds($ids);
        }
    }

    public function deleteByProductPrice(ProductPrice $productPrice): void
    {
        $this->deleteByProductPriceIds($productPrice->getId());
    }

    private function deleteByProductPriceIds(array|int $productPriceIds)
    {
        $this->createQueryBuilder("chpp")
            ->delete()
            ->andWhere("chpp.productPrice IN (:productPriceIds)")
            ->setParameter("productPriceIds", $productPriceIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Cart $cart
     * @param array $productIds
     * @return array<CartHasProductPrice>
     */
    public function getCartProductPriceByProductIdAndCartId(Cart $cart, array $productIds):array
    {
        return $this->createQueryBuilder("chpp")
            ->addSelect("pp")
            ->leftJoin("chpp.productPrice", "pp")
            ->andWhere("pp.product IN (:productIds)")
            ->andWhere("chpp.cart = :cartId")
            ->setParameter("productIds", $productIds)
            ->setParameter("cartId", $cart)
            ->getQuery()
            ->getResult();
    }
}
