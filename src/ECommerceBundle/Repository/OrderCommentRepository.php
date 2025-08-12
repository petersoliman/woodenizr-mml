<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\OrderComment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<OrderComment>
 *
 * @method OrderComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderComment[]    findAll()
 * @method OrderComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderCommentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderComment::class, "ol");
    }


}
