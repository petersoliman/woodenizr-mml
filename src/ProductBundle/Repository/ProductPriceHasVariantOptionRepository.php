<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\ProductPrice;
use App\ProductBundle\Entity\ProductPriceHasVariantOption;
use App\ProductBundle\Entity\ProductVariantOption;
use App\ProductBundle\Entity\SubAttribute;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ProductPriceHasVariantOption>
 *
 * @method ProductPriceHasVariantOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductPriceHasVariantOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductPriceHasVariantOption[]    findAll()
 * @method ProductPriceHasVariantOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductPriceHasVariantOptionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPriceHasVariantOption::class, "pphvo");
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('pphvo')
            ->addSelect("pv")
            ->addSelect("pvo")
            ->addSelect("pp")
            ->leftJoin("pphvo.variant", "pv")
            ->leftJoin("pphvo.option", "pvo")
            ->leftJoin("pphvo.productPrice", "pp");
    }

    public function countByOption(ProductVariantOption $option)
    {
        return $this->getStatement()
            ->select("COUNT(IDENTITY(pphvo.productPrice))")
            ->andWhere("pphvo.option=:optionId")
            ->andWhere("pp.deleted IS NULL")
            ->andWhere("pv.deleted IS NULL")
            ->andWhere("pvo.deleted IS NULL")
            ->setParameter("optionId", $option->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVariantsByProductPrices(array $productPrices)
    {
        $ids = [];
        foreach ($productPrices as $productPrice) {
            $ids[] = $productPrice->getId();
        }

        return $this->getStatement()
            ->andWhere("pphvo.productPrice IN (:productPriceIds)")
            ->andWhere("pp.deleted IS NULL")
            ->andWhere("pp.unitPrice > 0")
            ->andWhere("pv.deleted IS NULL")
            ->andWhere("pvo.deleted IS NULL")
            ->setParameter("productPriceIds", $ids)
            ->getQuery()
            ->getResult();
    }
}
