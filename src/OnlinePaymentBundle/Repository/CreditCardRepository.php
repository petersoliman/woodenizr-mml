<?php

namespace App\OnlinePaymentBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\OnlinePaymentBundle\Entity\CreditCard;
use App\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CreditCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditCard[]    findAll()
 * @method CreditCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditCardRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditCard::class, "cc");
    }

    public function getValidCreditCardByUser(User $user, bool $count = false)
    {
        $statement = $this->createQueryBuilder("cc")
            ->andWhere("cc.user = :userId")
            ->andWhere("cc.valid = 1")
            ->setParameter("userId", $user->getId());
        if ($count == true) {
            $statement->select("COUNT(cc.id)");

            return (int)$statement->getQuery()->getSingleScalarResult();
        }


        return $statement->getQuery()->getResult();
    }
}
