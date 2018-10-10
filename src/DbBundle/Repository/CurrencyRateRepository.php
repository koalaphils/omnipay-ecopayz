<?php

namespace DbBundle\Repository;

use DateTimeInterface;
use DbBundle\Entity\Currency;
use DbBundle\Entity\CurrencyRate;

class CurrencyRateRepository extends BaseRepository
{
    public function findLatestRate(Currency $currency): ?CurrencyRate
    {
        $queryBuilder = $this->createQueryBuilder('currencyRate');
        $queryBuilder
            ->select('currencyRate, sourceCurrency, destinationCurrency')
            ->innerJoin('currencyRate.sourceCurrency', 'sourceCurrency')
            ->innerJoin('currencyRate.destinationCurrency', 'destinationCurrency')
            ->where('sourceCurrency.id = :sourceCurrency and currencyRate.isLatest = :latest')
            ->setParameter('sourceCurrency', $currency->getId())
            ->setParameter('latest', true)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findRateOfCurrencyBeforeOrOnDate(Currency $currency, DateTimeInterface $date): ?CurrencyRate
    {
        $queryBuilder = $this->createQueryBuilder('currencyRate');
        $queryBuilder
            ->select('currencyRate, sourceCurrency, destinationCurrency')
            ->innerJoin('currencyRate.sourceCurrency', 'sourceCurrency')
            ->innerJoin('currencyRate.destinationCurrency', 'destinationCurrency')
            ->where('sourceCurrency.id = :sourceCurrency AND currencyRate.createdAt <= :date')
            ->setMaxResults(1)
            ->orderBy('currencyRate.createdAt', 'DESC')
            ->addOrderBy('currencyRate.id', 'DESC')
            ->setParameter('sourceCurrency', $currency->getId())
            ->setParameter('date', $date)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
    
    public function findRateWithSourceAndDestinationBeforeOrOnDate(
        Currency $sourceCurrency,
        Currency $destinationCurrency,
        DateTimeInterface $date
    ): ?CurrencyRate {
        $queryBuilder = $this->createQueryBuilder('currencyRate');
        $queryBuilder
            ->select('currencyRate, sourceCurrency, destinationCurrency')
            ->innerJoin('currencyRate.sourceCurrency', 'sourceCurrency')
            ->innerJoin('currencyRate.destinationCurrency', 'destinationCurrency')
            ->where('sourceCurrency.id = :sourceCurrency destinationCurrency.id = :destinationCurrency AND currencyRate.createdAt <= :date')
            ->setMaxResults(1)
            ->orderBy('currencyRate.createdAt', 'DESC')
            ->addOrderBy('currencyRate.id', 'DESC')
            ->setParameter('sourceCurrency', $sourceCurrency->getId())
            ->setParameter('destinationCurrency', $destinationCurrency->getId())
            ->setParameter('date', $date)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findCurrencyRateHistoryByCurrencyId(int $currencyId, int $numberToDisplay = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('currencyRate');
        $queryBuilder
            ->select('currencyRate, sourceCurrency, creator')
            ->innerJoin('currencyRate.sourceCurrency', 'sourceCurrency')
            ->leftJoin('currencyRate.creator', 'creator')
            ->where('sourceCurrency.id = :sourceCurrencyId')
            ->orderBy('currencyRate.createdAt', 'DESC')
            ->addOrderBy('currencyRate.id', 'DESC')
            ->setParameter('sourceCurrencyId', $currencyId)
        ;

        if ($numberToDisplay > 0) {
            $queryBuilder->setMaxResults($numberToDisplay);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
