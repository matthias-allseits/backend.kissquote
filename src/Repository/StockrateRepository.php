<?php

namespace App\Repository;

use App\Entity\Marketplace;
use App\Entity\Stockrate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stockrate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stockrate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stockrate[]    findAll()
 * @method Stockrate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockrateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stockrate::class);
    }

    public function getLastRateByIsinAndMarketAndCurrencyNameAndDate(string $isin, Marketplace $market, string $currencyName, \DateTime $date): ?Stockrate
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT r
                FROM App\Entity\Stockrate r
                WHERE r.isin = :isin
                    AND r.marketplace = :market
                    AND r.currencyName = :currencyName
                    AND r.date <= :date
                ORDER BY r.date DESC'
        )
            ->setParameter('isin', $isin)
            ->setParameter('market', $market)
            ->setParameter('currencyName', $currencyName)
            ->setParameter('date', $date->format('Y-m-d'))
        ;
        /** @var Stockrate[] $results */
        $results = $query->getResult();
        $result = null;
        if (count($results) > 0) {
            $result = $results[0];
        }

        return $result;
    }

}
