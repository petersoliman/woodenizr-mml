<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\CartHasUser;
use App\ECommerceBundle\Entity\OrderHasCoupon;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderHasCoupon>
 *
 * @method OrderHasCoupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderHasCoupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderHasCoupon[]    findAll()
 * @method OrderHasCoupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartHasUserRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartHasUser::class, "chu");
    }


}
