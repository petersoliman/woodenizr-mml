<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\ProductPriceHasVariantOption;
use App\ProductBundle\Entity\ProductVariant;
use App\ProductBundle\Entity\ProductVariantOption;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<ProductVariantOption>
 *
 * @method ProductVariantOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductVariantOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductVariantOption[]    findAll()
 * @method ProductVariantOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductVariantOptionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariantOption::class, "pvo");
    }


    public function getOptionsHasPriceByVariant(ProductVariant $variant)
    {
        return $this->getStatement()
            ->leftJoin(ProductPriceHasVariantOption::class, "pphvo", "WITH", "pphvo.option=pvo.id")
            ->leftJoin("pphvo.productPrice", "pp")
            ->andWhere("pp.unitPrice > 0")
            ->andWhere("pvo.variant = :variantId")
            ->andWhere("pp.deleted IS NULL")
            ->andWhere("pvo.deleted IS NULL")
            ->setParameter("variantId", $variant)
            ->getQuery()
            ->getResult();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('pvo');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "pvo.id",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('pvo.id LIKE :searchTerm '
                . 'OR pvo.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('pvo.id = :id');
            $statement->setParameter('id', $search->id);
        }

        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('pvo.id in (:ids)');
            $statement->setParameter('ids', $search->ids);
        }

        if (isset($search->variant) and $search->variant > 0) {
            $statement->andWhere('pvo.variant = :variant');
            $statement->setParameter('variant', $search->variant);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('pvo.deleted IS NOT NULL');
            } else {
                $statement->andWhere('pvo.deleted IS NULL');
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

        $statement->groupBy('pvo.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
