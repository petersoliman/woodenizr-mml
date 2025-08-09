<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\ProductBundle\Entity\Occasion;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method Occasion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Occasion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OccasionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occasion::class, "o");
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        $search = new \stdClass();
        $search->id = $id;
        $search->deleted = 0;
        $result = $this->filter($search);

        return reset($result);
    }

    public function getActiveOccasion(): ?Occasion
    {
        return $this->createQueryBuilder('o')
            ->addSelect("seo")
            ->leftJoin("o.seo", "seo")
            ->andWhere("o.active = 1")
            ->andWhere("o.deleted IS NULL")
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function clearAllActive()
    {
        return $this->createQueryBuilder("o")
            ->update()
            ->set("o.active", "0")
            ->getQuery()
            ->execute();
    }

    public function findAll(): array
    {
        return $this->findBy(array('deleted' => null));
    }

    public function updateNumberOfProducts(Occasion $occasion, int $numberOfProducts, int $noOfPublishProducts)
    {
        return $this->createQueryBuilder("o")
            ->update()
            ->set("o.noOfProducts", $numberOfProducts)
            ->set("o.noOfPublishProducts", $noOfPublishProducts)
            ->andWhere("o.id = :id")
            ->setParameter("id", $occasion->getId())
            ->getQuery()
            ->execute();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->leftJoin("o.productHasOccasions", "pho");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "o.id",
            "o.title",
            "o.active",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('o.id LIKE :searchTerm '
                . 'OR o.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%' . trim($search->string) . '%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('o.id = :id');
            $statement->setParameter('id', $search->id);
        }
        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('o.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }
        if (isset($search->active) and (is_bool($search->active) or in_array($search->active, [0, 1]))) {
            $statement->andWhere('o.active = :active');
            $statement->setParameter('active', $search->active);
        }


        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('o.deleted IS NOT NULL');
            } else {
                $statement->andWhere('o.deleted IS NULL');
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

        $statement->groupBy('o.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
