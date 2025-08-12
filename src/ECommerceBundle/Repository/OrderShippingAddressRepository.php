<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\OrderShippingAddress;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderShippingAddress>
 *
 * @method OrderShippingAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderShippingAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderShippingAddress[]    findAll()
 * @method OrderShippingAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderShippingAddressRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderShippingAddress::class, "osa");
    }


}
