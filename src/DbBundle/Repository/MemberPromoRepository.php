<?php

namespace DbBundle\Repository;

use DbBundle\Entity\MemberPromo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class MemberPromoRepository extends BaseRepository
{
    public function getReferralList(array $filters = [], array $orders = [], int $limit = 10, int $offset = 0, $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select('mpr');

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $queryBuilder->addOrderBy($order['column'], $order['dir']);
            }
        }

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        $result = $queryBuilder->getQuery()->getResult($hydrationMode);
    
        dump($result);

        return $result;
    }

    public function getReferralListFilterCount(array $filters = []): int
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select($queryBuilder->expr()->count('mpr.id'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getFilteredReferralNames(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mpr');

        if (!empty($filters['referrer'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mpr.referrer', ':referrer'))
                ->setParameter('referrer', $filters['referrer']);
        }

        if (!empty($filters['member'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mpr.member', ':member'))
                ->setParameter('member', $filters['member']);
        }

        if (!empty($filters['promo'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mpr.promo', ':promo'))
                ->setParameter('promo', $filters['promo']);
        }

        if (!empty($filters['promoCode'])) {
            $queryBuilder->innerJoin('mpr.promo', 'p');
            $queryBuilder->andWhere('p.code = :code')->setParameter('code', $filters['promoCode']);
        }

        if (!empty($filters['transaction'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mpr.transaction', ':transaction'))
                ->setParameter('transaction', $filters['transaction']);
        }

        $hasTransaction = array_get($filters, 'hasTransaction', false);
        if ($hasTransaction) {
            $queryBuilder->andWhere('mpr.transaction IS NOT NULL');
        }

        return $queryBuilder;
    }

    public function findReferralMemberPromo($filters, $hydrationMode = Query::HYDRATE_OBJECT): ?MemberPromo
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select('mpr');

        if (!empty($filters['limit'])) {
            $queryBuilder->setMaxResults($filters['limit']);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function findReferredMembers($filters): ?array
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select(
            'COALESCE(SUM(transaction.amount),0) totalEarnings',
            'COUNT(mpr.id) as totalReferrals'
        );
        $queryBuilder->innerJoin('mpr.transaction', 'transaction');

        return $queryBuilder->getQuery()->getResult();
    }
}