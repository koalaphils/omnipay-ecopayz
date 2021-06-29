<?php

namespace DbBundle\Repository;

use DateTimeInterface;
use DbBundle\Entity\ProductCommission;

/**
 * This class should be named as ProductCommissionPercentage
 */
class ProductCommissionRepository extends BaseRepository
{
    public function getProductCommissionOfProducts(array $productIds): array
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder
            ->select('commission, product')
            ->innerJoin('commission.product', 'product')
            ->where('product.id IN (:productIds) AND commission.isLatest = :isLatest')
        ;
        $queryBuilder->setParameter('productIds', $productIds);
        $queryBuilder->setParameter('isLatest', true);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getProductCommissionOfProduct(int $productId): ?ProductCommission
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder
            ->select('commission, product')
            ->innerJoin('commission.product', 'product')
            ->where('product.id = :productId AND commission.isLatest = :isLatest')
        ;
        $queryBuilder->setParameter('productId', $productId);
        $queryBuilder->setParameter('isLatest', true);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findProductCommissionBeforeOrOnDate(int $productId, DateTimeInterface $date): ?ProductCommission
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder
            ->select('commission, product')
            ->innerJoin('commission.product', 'product')
            ->where('product.id = :productId')
            ->andWhere('commission.createdAt <= :date')
            ->setMaxResults(1)
            ->orderBy('commission.createdAt', 'DESC')
            ->addOrderBy('commission.id', 'DESC')
        ;
        $queryBuilder->setParameter('productId', $productId);
        $queryBuilder->setParameter('date', $date);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
