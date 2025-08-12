<?php

namespace App\ECommerceBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ECommerceBundle\Entity\Order;
use App\ECommerceBundle\Enum\OrderStatusEnum;
use App\ECommerceBundle\Enum\ShippingStatusEnum;
use App\OnlinePaymentBundle\Enum\PaymentStatusEnum;
use App\UserBundle\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class, "o");
    }

    public function getParentAndChildrenByParentOrder(Order $order): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id = :orderId')
            ->orWhere('o.parent = :orderId')
            ->setParameter('orderId', $order->getId())
            ->getQuery()
            ->getResult();
    }

    public function getTotalPrice(Order $order): float
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id = :orderId')
            ->orWhere('o.parent = :orderId')
            ->setParameter('orderId', $order->getId())
            ->select('SUM(o.totalPrice) as totalPrice')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    protected function getStatement(): QueryBuilder
    {
        $statement = $this->createQueryBuilder('o')
            ->addSelect('u')
            ->addSelect('pm')
            ->leftJoin("o.user", 'u')
            ->leftJoin("o.paymentMethod", 'pm')
            ->leftJoin("o.orderGuestData", 'ogd')
            ->leftJoin("o.orderHasCoupon", 'ohc')
            ->leftJoin("o.orderShippingAddress", 'osa');
        $statement
            ->leftJoin("o.orderHasProductPrices", 'ohpp')
            ->leftJoin("ohpp.productPrice", 'pp')
            ->leftJoin("pp.product", 'p');

        return $statement;
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'o.id',
            'o.paymentState',
            'o.shippingState',
            "u.fullName",
            "o.itemCount",
            "pm.title",
            "o.totalPrice",
            'o.created',
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterAddWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere(
                'o.id LIKE :searchTerm ' .
                'OR u.email LIKE :searchTerm ' .
                'OR u.fullName LIKE :searchTerm ' .
                'OR osa.mobileNumber LIKE :searchTerm ' .
                'OR ogd.name LIKE :searchTerm ' .
                'OR ogd.email LIKE :searchTerm '
            );
        }
        if (isset($search->minPrice) and Validate::not_null($search->minPrice)) {
            $statement->andWhere('o.totalPrice >= :minPrice');
        }

        if (isset($search->maxPrice) and Validate::not_null($search->maxPrice)) {
            $statement->andWhere('o.totalPrice <= :maxPrice');
        }
        if (isset($search->user) and Validate::not_null($search->user)) {
            $statement->andWhere('u.id = :user');
        }
        if (isset($search->state) and is_array($search->state)) {
            $statement->andWhere('o.state IN(:state)');
        } elseif (isset($search->state) and ($search->state instanceof OrderStatusEnum or Validate::not_null($search->state))) {
            $statement->andWhere('o.state = :state');
        }

        if (isset($search->paymentState) and is_array($search->paymentState)) {
            $statement->andWhere('o.paymentState IN(:paymentState)');
        } elseif (isset($search->paymentState) and ($search->paymentState instanceof PaymentStatusEnum or Validate::not_null($search->paymentState))) {
            $statement->andWhere('o.paymentState = :paymentState');
        }

        if (isset($search->paymentMethod) and is_array($search->paymentMethod)) {
            $statement->andWhere('o.paymentMethod IN(:paymentMethod)');
        } elseif (isset($search->paymentMethod) and Validate::not_null($search->paymentMethod)) {
            $statement->andWhere('o.paymentMethod = :paymentMethod');
        }
        if (isset($search->shippingState) and is_array($search->shippingState)) {
            $statement->andWhere('o.shippingState IN(:shippingState)');
        } elseif (isset($search->shippingState) and ($search->shippingState instanceof ShippingStatusEnum or Validate::not_null($search->shippingState))) {
            $statement->andWhere('o.shippingState = :shippingState');
        }
        if (isset($search->couponCode) and Validate::not_null($search->couponCode)) {
            $statement->andWhere('ohc.code = :couponCode');
        }
        if (isset($search->zone) and Validate::not_null($search->zone)) {
            $statement->andWhere('osa.city = :zone');
        }
        if (isset($search->from) and Validate::not_null($search->from)) {
            $statement->andWhere("o.created >= STR_TO_DATE(:from, '%d/%m/%Y')");
        }

        if (isset($search->to) and Validate::not_null($search->to)) {
            $statement->andWhere("o.created <=  DATE_ADD(STR_TO_DATE(:to, '%d/%m/%Y') , 1, 'DAY')");
        }
        if (isset($search->year) and Validate::not_null($search->year)) {
            $statement->andWhere('YEAR(o.created) = :year');
        }

        if (isset($search->month) and Validate::not_null($search->month)) {
            $statement->andWhere('MONTH(o.created) = :month');
        }
    }

    private function filterAddWhereSetParameter(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->minPrice) and Validate::not_null($search->minPrice)) {
            $statement->setParameter('minPrice', $search->minPrice);
        }

        if (isset($search->maxPrice) and Validate::not_null($search->maxPrice)) {
            $statement->setParameter('maxPrice', $search->maxPrice);
        }

        if (isset($search->user) and Validate::not_null($search->user)) {
            $statement->setParameter('user', $search->user);
        }
        if (isset($search->zone) and Validate::not_null($search->zone)) {
            $statement->setParameter('zone', $search->zone);
        }

        if (isset($search->state) and is_array($search->state)) {
            $statement->setParameter('state', $search->state);
        } elseif (isset($search->state) and $search->state instanceof OrderStatusEnum) {
            $statement->setParameter('state', $search->state->value);
        } elseif (isset($search->state) and Validate::not_null($search->state)) {
            $statement->setParameter('state', $search->state);
        }

        if (isset($search->paymentState) and is_array($search->paymentState)) {
            $statement->setParameter('paymentState', $search->paymentState);
        } elseif (isset($search->paymentState) and $search->paymentState instanceof PaymentStatusEnum) {
            $statement->setParameter('paymentState', $search->paymentState->value);
        } elseif (isset($search->paymentState) and Validate::not_null($search->paymentState)) {
            $statement->setParameter('paymentState', $search->paymentState);
        }

        if (isset($search->paymentMethod) and is_array($search->paymentMethod)) {
            $statement->setParameter('paymentMethod', $search->paymentMethod);
        } elseif (isset($search->paymentMethod) and Validate::not_null($search->paymentMethod)) {
            $statement->setParameter('paymentMethod', $search->paymentMethod);
        }


        if (isset($search->shippingState) and is_array($search->shippingState)) {
            $statement->setParameter('shippingState', $search->shippingState);
        } elseif (isset($search->shippingState) and $search->shippingState instanceof ShippingStatusEnum) {
            $statement->setParameter('shippingState', $search->shippingState->value);
        } elseif (isset($search->shippingState) and Validate::not_null($search->shippingState)) {
            $statement->setParameter('shippingState', $search->shippingState);
        }

        if (isset($search->couponCode) and Validate::not_null($search->couponCode)) {
            $statement->setParameter('couponCode', $search->couponCode);
        }
        if (isset($search->from) and Validate::not_null($search->from)) {
            $statement->setParameter('from', $search->from);
        }

        if (isset($search->to) and Validate::not_null($search->to)) {
            $statement->setParameter('to', $search->to);
        }

        if (isset($search->year) and Validate::not_null($search->year)) {
            $statement->setParameter('year', $search->year);
        }

        if (isset($search->month) and Validate::not_null($search->month)) {
            $statement->setParameter('month', $search->month);
        }
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        $this->filterAddWhereClause($statement, $search);
        $this->filterAddWhereSetParameter($statement, $search);
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('o.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }

    public function getTotalSuccessOrder($search): float
    {
        $statement = $this->getStatement();
        $statement->select("DISTINCT o.id");
        $this->filterAddWhereClause($statement, $search);

        $queryBuilder = $this->createQueryBuilder("ot");
        $totalStatement = $queryBuilder
            ->andWhere($queryBuilder->expr()->in('ot.id', $statement->getDQL()));
        $totalStatement->select("SUM(ot.totalPrice) totalPrice");

        $this->filterAddWhereSetParameter($totalStatement, $search);

        return (float)$totalStatement->getQuery()->getSingleScalarResult();
    }

    public function getNoOfSuccessOrderByUser(User $user): int
    {
        return $this->getStatement()
            ->select("COUNT(DISTINCT {$this->tableAlias}.id)")
            ->andWhere("u.id = :userId")
            ->andWhere("o.state = :state")
            ->setParameter("userId", $user)
            ->setParameter("state", OrderStatusEnum::SUCCESS)
            ->setMaxResults(1)
            ->getQuery()->getSingleScalarResult();
    }

    public function checkOrderExist(
        $userId,
        OrderStatusEnum $orderState,
        $subTotal,
        $shippingAddressId,
        $productPricesHash
    ): ?Order
    {
        return $this->getStatement()
            ->andWhere("u.id = :userId")
            ->andWhere("o.state = :orderState")
            ->andWhere("o.subTotal = :subTotal")
            ->andWhere("osa.shippingAddress = :shippingAddressId")
            ->andWhere("o.cartProductPricesHash = :productPricesHash")
            ->setParameter('userId', $userId)
            ->setParameter('orderState', $orderState)
            ->setParameter('subTotal', $subTotal)
            ->setParameter('shippingAddressId', $shippingAddressId)
            ->setParameter('productPricesHash', $productPricesHash)
            ->setMaxResults(1)
            ->orderBy("o.id", "DESC")
            ->getQuery()->getOneOrNullResult();
    }

    public function getAverageOrderValue(\stdClass $search): float
    {
        $statement = $this->getStatement();
        $statement->select("DISTINCT o.id");
        $this->filterAddWhereClause($statement, $search);

        $queryBuilder = $this->createQueryBuilder("ot");
        $totalStatement = $queryBuilder
            ->andWhere($queryBuilder->expr()->in('ot.id', $statement->getDQL()));
        $totalStatement->select("SUM(ot.totalPrice)/COUNT(DISTINCT ot.id)  totalPrice");

        $this->filterAddWhereSetParameter($totalStatement, $search);

        return (float)$totalStatement->getQuery()->getSingleScalarResult();
    }


    /**
     * get count of ProductPrices by order id
     * @param Order $order
     * @return int
     * @throws Exception
     */
    public function getOrderProductPriceByOrderId(Order $order): int
    {
        return $this->createQueryBuilder("o")
            ->select("COUNT(ohpp.productPrice)")
            ->leftJoin("o.orderHasProductPrices", "ohpp")
            ->andWhere("o.id = :orderId")
            ->setParameter("orderId", $order->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
