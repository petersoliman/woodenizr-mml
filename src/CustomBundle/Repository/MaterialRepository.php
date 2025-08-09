<?php

namespace App\CustomBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CustomBundle\Entity\Material;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @extends BaseRepository<Material>
 *
 * @method Material|null find($id, $lockMode = null, $lockVersion = null)
 * @method Material|null findOneBy(array $criteria, array $orderBy = null)
 * @method Material[]    findAll()
 * @method Material[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaterialRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Material::class, "m");
    }
    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->addSelect("-m.tarteb AS HIDDEN inverseTarteb")
            ->addSelect("mTrans")
            ->leftJoin("m.translations", "mTrans");
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('m.id LIKE :searchTerm '
                .'OR m.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('m.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }
        if (isset($search->category) and $search->category != "") {
            $statement->andWhere('m.category = :category');
            $statement->setParameter('category', $search->category);
        }
        if (isset($search->notId) and $search->notId != "") {
            $statement->andWhere('m.id <> :notId');
            $statement->setParameter('notId', $search->notId);
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('m.deleted IS NOT NULL');
            } else {
                $statement->andWhere('m.deleted IS NULL');
            }
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'm.id',
            'inverseTarteb',
            'm.title',
            'm.created',
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

        $statement->groupBy('m.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
    

}
