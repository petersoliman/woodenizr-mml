<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\OrderGuestData;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderGuestData>
 *
 * @method OrderGuestData|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderGuestData|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderGuestData[]    findAll()
 * @method OrderGuestData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderGuestDataRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderGuestData::class, "ol");
    }


}
