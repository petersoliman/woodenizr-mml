<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\OrderHasProductPrice;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderHasProductPrice>
 *
 * @method OrderHasProductPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderHasProductPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderHasProductPrice[]    findAll()
 * @method OrderHasProductPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderHasProductPriceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderHasProductPrice::class, "ohpp");
    }


}
