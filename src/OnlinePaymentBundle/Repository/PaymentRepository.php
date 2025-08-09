<?php

namespace App\OnlinePaymentBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\OnlinePaymentBundle\Entity\Payment;
use App\OnlinePaymentBundle\Enum\PaymentTypesEnum;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class, "p");
    }

    public function getOneByTypeAndObjectId(PaymentTypesEnum $paymentType, int $objectId): ?Payment
    {
        return $this->createQueryBuilder("p")
            ->andWhere("p.objectId = :objectId")
            ->andWhere("p.type = :paymentType")
            ->setParameter("objectId", $objectId)
            ->setParameter("paymentType", $paymentType)
            ->orderBy("p.id", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
