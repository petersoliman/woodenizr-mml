<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\Coupon;
use App\ECommerceBundle\Entity\OrderHasCoupon;
use App\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderHasCoupon>
 *
 * @method OrderHasCoupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderHasCoupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderHasCoupon[]    findAll()
 * @method OrderHasCoupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderHasCouponRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderHasCoupon::class, "ohc");
    }

    public function numberOfUse(?Coupon $coupon, ?User $user = null)
    {
        $statement = $this->createQueryBuilder("ohc")
            ->select("COUNT(DISTINCT ohc.order)")
            ->leftJoin("ohc.order", "o")
            ->andWhere("ohc.coupon = :couponId")
            ->setParameter("couponId", $coupon);
        
        if ($user instanceof User) {
            $statement->andWhere("o.user = :userId")
                ->setParameter("userId", $user->getId());
        }

        $statement->setMaxResults(1);
        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return (int)reset($count);
        }

        return 0;
    }


}
