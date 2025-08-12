<?php

namespace App\ProductBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\MediaBundle\Entity\Image;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\ProductBundle\Entity\Collection;
use PN\ServiceBundle\Utils\Validate;

class CollectionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class, "c");
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        $search = new \stdClass();
        $search->id = $id;
        $search->deleted = 0;
        $result = $this->filter($search);

        return reset($result);
    }

    public function updateNumberOfProducts(Collection $collection, int $numberOfProducts, int $noOfPublishProducts)
    {
        return $this->createQueryBuilder("c")
            ->update()
            ->set("c.noOfProducts", $numberOfProducts)
            ->set("c.noOfPublishProducts", $noOfPublishProducts)
            ->andWhere("c.id = :id")
            ->setParameter("id", $collection->getId())
            ->getQuery()
            ->execute();
    }

    public function findAll(): array
    {
        return $this->findBy(['deleted' => null]);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->addSelect("-c.tarteb AS HIDDEN inverseTarteb")
            ->leftJoin("c.productHasCollections", "phc");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "inverseTarteb",
            "c.title",
            "c.publish",
            "c.featured",
            "c.created",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search): void
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('c.id LIKE :searchTerm '
                .'OR c.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('c.id = :id');
            $statement->setParameter('id', $search->id);
        }
        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('c.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }

        if (isset($search->featured) and (is_bool($search->featured) or in_array($search->featured, [0, 1]))) {
            $statement->andWhere('c.featured = :featured');
            $statement->setParameter('featured', $search->featured);
        }

        if (isset($search->hasProducts) and is_bool($search->hasProducts) and $search->hasProducts === true) {
            $statement->andWhere('c.noOfPublishProducts > 0');
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('c.deleted IS NOT NULL');
            } else {
                $statement->andWhere('c.deleted IS NULL');
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

        $statement->groupBy('c.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
