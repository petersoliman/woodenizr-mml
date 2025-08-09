<?php

namespace App\UserBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Date;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends BaseRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class, "u");
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @param string $role
     *
     * @return array
     */
    public function findByRole(string $role): array
    {
        $statement = $this->createQueryBuilder("u")
            ->select('u')
            ->where('u.roles LIKE :roles')
            ->andWhere('u.deleted IS NULL')
            ->orderBy('u.id', 'DESC')
            ->setParameter('roles', '%"' . $role . '"%');

        return $statement->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function findAllUsers(): array
    {
        $statement = $this->createQueryBuilder("u")
            ->where('u.deleted IS NULL')
            ->orderBy('u.id', 'DESC');

        return $statement->getQuery()->getResult();
    }

    public function getNoOfUserRegisteredByMonthAndYear($month, $year): int
    {
        return $this->createQueryBuilder("u")
            ->select("COUNT(u.id)")
            ->andWhere("u.deleted IS NULL")
            ->andWhere("MONTH(u.created) = :month")
            ->andWhere("YEAR(u.created) = :year")
            ->setParameter("month", $month)
            ->setParameter("year", $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateCartItemsNoByUser(User $user, $cartItemsNo): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "UPDATE `user` SET cart_item_no=:cartItemsNo WHERE id=:userId";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userId", $user->getId());
        $statement->bindValue("cartItemsNo", $cartItemsNo);
        $statement->executeQuery();
    }

    public function updateNoOfSuccessOrderByUser(User $user, $noOfSuccessOrder): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "UPDATE `user` SET success_order_no=:noOfSuccessOrder WHERE id=:userId";
        $statement = $connection->prepare($sql);
        $statement->bindValue("userId", $user->getId());
        $statement->bindValue("noOfSuccessOrder", $noOfSuccessOrder);
        $statement->executeQuery();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('u');
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('u.id LIKE :searchTerm '
                . 'OR u.fullName LIKE :searchTerm '
                . 'OR u.email LIKE :searchTerm '
                . 'OR u.phone LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->role) and Validate::not_null($search->role)) {
            if ($search->role == User::ROLE_DEFAULT) {
                $statement->andWhere("u.roles = :role");
                $statement->setParameter("role", "[]");
            } else {
                $roles = (!is_array($search->role)) ? [$search->role] : $search->role;
                $roleClause = null;
                $i = 0;
                foreach ($roles as $value) {
                    if ($i > 0) {
                        $roleClause .= " OR ";
                    }
                    $roleClause .= " u.roles LIKE :role" . $i;
                    $statement->setParameter('role' . $i, '%' . trim($value) . '%');
                    $i++;
                }
                $statement->andWhere($roleClause);
            }

        }
        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('u.id in (:ids)');
            $statement->setParameter('ids', $search->ids);
        }

        if (isset($search->enabled) and (is_bool($search->enabled) or in_array($search->enabled, [0, 1]))) {
            $statement->andWhere('u.enabled = :enabled');
            $statement->setParameter('enabled', $search->enabled);
        }
        if (isset($search->regDateFrom) and Validate::not_null($search->regDateFrom)) {
            $convertedDate = Date::convertDateFormat($search->regDateFrom, Date::DATE_FORMAT3, Date::DATE_FORMAT2);
            $statement->andWhere("DATEDIFF(u.created, :regDateFrom) >= 0");
            $statement->setParameter('regDateFrom', $convertedDate);
        }
        if (isset($search->regDateTo) and Validate::not_null($search->regDateTo)) {
            $convertedDate = Date::convertDateFormat($search->regDateTo, Date::DATE_FORMAT3, Date::DATE_FORMAT2);
            $statement->andWhere("DATEDIFF(u.created, :regDateTo) <= 0");
            $statement->setParameter('regDateTo', $convertedDate);
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('u.deleted IS NOT NULL');
            } else {
                $statement->andWhere('u.deleted IS NULL');
            }
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'u.id',
            'u.fullName',
            'u.email',
            'u.phone',
            'u.lastLogin',
            'u.cartItemsNo',
            'u.successOrderNo',
            'u.created',
            'u.enabled',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }
        $statement->groupBy('u.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
