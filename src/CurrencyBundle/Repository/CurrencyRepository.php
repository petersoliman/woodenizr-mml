<?php

namespace App\CurrencyBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CurrencyBundle\Entity\Currency;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PN\ServiceBundle\Utils\Validate;

/**
 * @method Currency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Currency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Currency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class, "c");
    }

    public function findAll(): array
    {
        return $this->findBy(["deleted" => null]);
    }

    public function findOneByCode($code): ?Currency
    {
        return $this->findOneBy(["code" => $code, "deleted" => null]);
    }

    public function getDefaultCurrency(): ?Currency
    {
        $currency = $this->findOneBy(["default" => true, "deleted" => null]);
        
        // If no default currency exists, try to get any available currency
        if ($currency === null) {
            $currency = $this->findOneBy(["deleted" => null]);
        }
        
        return $currency;
    }

    public function changeDefault(Currency $currency): void
    {
        $this->createQueryBuilder("c")
            ->update()
            ->set("c.default", 0)
            ->getQuery()
            ->execute();

        $currency->setDefault(true);
        $this->getEntityManager()->persist($currency);
        $this->getEntityManager()->flush();
    }

    protected function getStatement(): QueryBuilder
    {
        return $this->createQueryBuilder("c")
            ->addSelect("trans")
            ->addSelect("transLang")
            ->leftJoin("c.translations", "trans")
            ->leftJoin("trans.language", "transLang");
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search): void
    {
        $sortSQL = [
            "{$this->tableAlias}.code",
            "{$this->tableAlias}.title",
            "{$this->tableAlias}.symbol",
            "{$this->tableAlias}.default",
            "{$this->tableAlias}.created",
        ];

        $this->filterOrderLogic($statement, $search, $sortSQL);
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::not_null($search->string)) {
            $statement->andWhere("{$this->tableAlias}.code LIKE :searchTerm "
                ."OR {$this->tableAlias}.title LIKE :searchTerm "
                ."OR {$this->tableAlias}.symbol LIKE :searchTerm "
            );
            $statement->setParameter("searchTerm", "%".trim($search->string)."%");
        }

        if (isset($search->deleted) and in_array($search->deleted, [0, 1])) {
            if ($search->deleted == 1) {
                $statement->andWhere("{$this->tableAlias}.deleted IS NOT NULL");
            } else {
                $statement->andWhere("{$this->tableAlias}.deleted IS NULL");
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

        $statement->groupBy("{$this->tableAlias}.id");
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
