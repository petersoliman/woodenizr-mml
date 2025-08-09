<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\OrderLog;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderLog>
 *
 * @method OrderLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderLog[]    findAll()
 * @method OrderLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderLogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderLog::class, "ol");
    }


}
