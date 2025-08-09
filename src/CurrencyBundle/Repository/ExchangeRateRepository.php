<?php

namespace App\CurrencyBundle\Repository;

use App\BaseBundle\Repository\BaseRepository;
use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Entity\ExchangeRate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExchangeRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExchangeRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExchangeRate[]    findAll()
 * @method ExchangeRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExchangeRateRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class, "e");
    }

    public function getExchangeRate(
        Currency $sourceCurrency,
        Currency $targetCurrency,
    ): float {
        $result = $this->createQueryBuilder('e')
            ->select('e.ratio AS ratio')
            ->andWhere('e.sourceCurrency = :sourceCurrencyId')
            ->andWhere('e.targetCurrency = :targetCurrencyId')
            ->setParameter('sourceCurrencyId', $sourceCurrency->getId())
            ->setParameter('targetCurrencyId', $targetCurrency->getId())
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($result != null) {
            return ($result['ratio'] > 0) ? $result['ratio'] : 1;
        }

        return 1;
    }
}
