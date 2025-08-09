<?php

namespace App\ShippingBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ShippingBundle\Entity\ShippingAddress;
use App\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<ShippingAddress>
 *
 * @method ShippingAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingAddressRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingAddress::class, "sa");
    }

    public function findAll(): array
    {
        return $this->findBy(['deleted' => null]);
    }

    public function getValidByUser(User $user)
    {
        return $this->getStatement()
            ->andWhere("sa.user = :userId")
            ->andWhere("sa.zone IS NOT NULL")
//            ->andWhere("c.publish = 1")
            ->andWhere("sa.deleted IS NULL")
            ->orderBy("sa.id", "DESC")
            ->setParameter("userId", $user->getId())
            ->getQuery()
            ->getResult();

    }

    public function removeDefault(User $user)
    {
        return $this->createQueryBuilder('sa')
            ->update()
            ->set("sa.default", "false")
            ->andWhere("sa.user= :userId")
            ->setParameter("userId", $user->getId())
            ->getQuery()->execute();
    }

    public function makeFirstAddressDefault(User $user): void
    {
        $this->removeDefault($user);
        $entity = $this->findOneBy(["user" => $user, "deleted" => null]);
        $entity->setDefault(true);
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    public function getUserDefaultAddress(User $user): ?ShippingAddress
    {
        return $this->createQueryBuilder('sa')
            ->andWhere("sa.deleted IS NULL")
            ->andWhere("sa.user= :userId")
            ->andWhere("sa.default = 1")
            ->setMaxResults(1)
            ->setParameter("userId", $user->getId())
            ->getQuery()->getOneOrNullResult();
    }

    public function isUserHasDefaultAddress(User $user): bool
    {
        $entity = $this->createQueryBuilder('sa')
            ->select("sa.id")
            ->andWhere("sa.deleted IS NULL")
            ->andWhere("sa.user= :userId")
            ->andWhere("sa.default = 1")
            ->setMaxResults(1)
            ->setParameter("userId", $user->getId())
            ->getQuery()->getOneOrNullResult();

        if ($entity == null) {
            return false;
        }
        return true;
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('sa')
            ->addSelect("c")
            ->leftJoin("sa.zone", "c");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'sa.id',
            'c.title',
            'sa.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('sa.title LIKE :searchTerm '
                . 'OR c.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->user) and $search->user > 0) {
            $statement->andWhere('sa.user = :user');
            $statement->setParameter('user', $search->user);
        }
        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('c.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('sa.deleted IS NOT NULL');
            } else {
                $statement->andWhere('sa.deleted IS NULL');
            }
        }
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('sa.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }


}
