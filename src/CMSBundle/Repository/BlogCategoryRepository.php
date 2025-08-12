<?php

namespace App\CMSBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CMSBundle\Entity\BlogCategory;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method BlogCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogCategory[]    findAll()
 * @method BlogCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogCategoryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogCategory::class, "bc");
    }

    public function getMenuCategories($limit = 5): array
    {
        $search = new \stdClass();
        $search->deleted = 0;
        $search->publish = 1;
        $search->showInMenu = true;
        $search->ordr = ["column" => 1, "dir" => "DESC"];

        return $this->filter($search, false, 0, $limit);
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder('bc')
            ->addSelect("-bc.tarteb AS HIDDEN inverseTarteb")
            ->leftJoin("bc.translations", "trans")
            ->leftJoin("bc.seo", "seo")
            ->leftJoin("seo.translations", "seoTrans");
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere('bc.id LIKE :searchTerm '
                .'OR bc.title LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->showInMenu) and is_bool($search->showInMenu)) {
            $statement->andWhere('bc.showInMenu = :showInMenu');
            $statement->setParameter('showInMenu', $search->showInMenu);
        }

        if (isset($search->publish) and (is_bool($search->publish) or in_array($search->publish, [0, 1]))) {
            $statement->andWhere('bc.publish = :publish');
            $statement->setParameter('publish', $search->publish);
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere('bc.deleted IS NOT NULL');
            } else {
                $statement->andWhere('bc.deleted IS NULL');
            }
        }
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            'bc.id',
            'inverseTarteb',
            'bc.title',
            'bc.created',
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

        $statement->groupBy('bc.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
