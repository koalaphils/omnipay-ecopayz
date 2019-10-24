<?php

namespace DbBundle\Repository;

use DateTimeInterface;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberRevenueShare;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

class MemberRevenueShareRepository extends BaseRepository
{
    public function findByMemberIdAndProductId(int $memberId, int $productId): ?MemberRevenueShare
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        $queryBuilder->where('revenue.member = :memberId AND revenue.product = :productId AND revenue.isLatest = :isLatest');
        $queryBuilder->setParameters([
            'memberId' => $memberId,
            'productId' => $productId,
            'isLatest' => true,
        ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByMemberIdAndProductIdGroup(int $memberId, int $productId): ?MemberRevenueShare
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        $queryBuilder->where('revenue.member = :memberId AND revenue.product = :productId AND revenue.isLatest = :isLatest');
        $queryBuilder->setParameters([
            'memberId' => $memberId,
            'productId' => $productId,
            'isLatest' => true,
        ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByMemberId(int $memberId): ?array
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        $queryBuilder->where('revenue.member = :memberId AND revenue.isLatest = :isLatest');
        $queryBuilder->setParameters([
            'memberId' => $memberId,
            'isLatest' => true,
        ]);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findRevenueShareByResourceId(string $resourceId): ?MemberRevenueShare
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        $queryBuilder
            ->where('revenue.resourceId = :resourceId and revenue.isLatest = :isLatest')
            ->setParameters([
                'resourceId' => $resourceId,
                'isLatest' => true,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findRevenueShareOfCustomerForProductBeforeOrOnDate(
        Member $member,
        int $product,
        DateTimeInterface $forDate
    ): ?MemberRevenueShare {
        $queryBuilder = $this->createFilterQueryBuilder([
            'member' => $member->getId(),
            'product' => $product,
            'beforeOrEqual' => $forDate]
        );
        $queryBuilder->orderBy('revenue.createdAt', 'DESC')->addOrderBy('revenue.id', 'DESC');
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function createFilterQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        if (array_get($filters, 'member', null) !== null) {
            $queryBuilder->andWhere('revenue.member = :memberId');
            $queryBuilder->setParameter('memberId', array_get($filters, 'member'));
        }

        if (array_get($filters, 'status', null) !== null) {
            $queryBuilder->andWhere('revenue.status = :status');
            $queryBuilder->setParameter('status', array_get($filters, 'status'));
        }

        if (array_get($filters, 'product', null) !== null) {
            $queryBuilder
                ->andWhere('revenue.product = :productId')
                ->setParameter('productId', array_get($filters, 'product'))
            ;
        }

        if (array_get($filters, 'beforeOrEqual',  null) !== null) {
            $queryBuilder
                ->andWhere('revenue.createdAt <= :beforeOrEqual')
                ->setParameter('beforeOrEqual', array_get($filters, 'beforeOrEqual'))
            ;
        }

        if (array_get($filters, 'dateFrom',  null) !== null) {
            $queryBuilder
                ->andWhere('revenue.createdAt >= :dateFrom')
                ->setParameter('dateFrom', array_get($filters, 'dateFrom'))
            ;
        }

        if (array_get($filters, 'dateTo',  null) !== null) {
            $queryBuilder
                ->andWhere('revenue.createdAt <= :dateTo')
                ->setParameter('dateTo', array_get($filters, 'dateTo'))
            ;
        }

        if (array_get($filters, 'latest', null) !== null) {
            $queryBuilder->andWhere('revenue.isLatest = :isLatest')->setParameter('isLatest', array_get($filters, 'latest', true));
        }

        return $queryBuilder;
    }

    public function findSchemeByRange(
        int $memberId,
        int $productId,
        array $filters
    ): array {
        $this->setToBuffered();

        $dwlDateFrom = "";
        if (array_has($filters, 'dwlDateFrom')) {
            $dwlDateFrom = $filters['dwlDateFrom'];
        }

        $dwlDateTo = "";
        if (array_has($filters, 'dwlDateTo')) {
            $dwlDateTo = $filters['dwlDateTo'];
        }     

        $beforeDateSql = " SELECT date_format(max(m2.member_revenue_share_created_at), '%Y-%m-%d') AS createdAt, m2.member_revenue_share_settings AS revenueShareSettings FROM member_revenue_share m2
            WHERE m2.member_revenue_share_member_id = :memberId
            AND m2.member_revenue_share_product_id = :productId ";

        $inBetweenDateSql = " SELECT date_format(max(m1.member_revenue_share_created_at), '%Y-%m-%d') AS createdAt, m1.member_revenue_share_settings AS revenueShareSettings FROM member_revenue_share m1
            WHERE m1.member_revenue_share_member_id = :memberId
            AND m1.member_revenue_share_product_id = :productId ";
        
        if ($dwlDateFrom && $dwlDateTo) {
            $beforeDateSql .= " AND (date_format(m2.member_revenue_share_created_at, '%Y-%m-%d') < :dwlDateFrom)";
            $inBetweenDateSql .= " AND (date_format(m1.member_revenue_share_created_at, '%Y-%m-%d') >= :dwlDateFrom
                                   AND date_format(m1.member_revenue_share_created_at, '%Y-%m-%d') <= :dwlDateTo)";
        }
                 
        $beforeDateSql .= " GROUP BY date_format(m2.member_revenue_share_created_at, '%Y-%m-%d'), m2.member_revenue_share_settings
                            ORDER BY date_format(m2.member_revenue_share_created_at, '%Y-%m-%d') DESC limit 0, 1";
        $inBetweenDateSql .= " GROUP BY date_format(m1.member_revenue_share_created_at, '%Y-%m-%d'), m1.member_revenue_share_settings";

        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('revenueShareSettings','revenueShareSettings');
        $resultsMap->addScalarResult('createdAt','createdAt');

        $beforeDateQuery = $this->getEntityManager()->createNativeQuery($beforeDateSql, $resultsMap);
        $inBetweenDateQuery = $this->getEntityManager()->createNativeQuery($inBetweenDateSql, $resultsMap);

        $beforeDateQuery->setParameter('memberId', $memberId);
        $beforeDateQuery->setParameter('productId', $productId);
        $beforeDateQuery->setParameter('dwlDateFrom', $dwlDateFrom);
        $inBetweenDateQuery->setParameter('memberId', $memberId);
        $inBetweenDateQuery->setParameter('productId', $productId);
        $inBetweenDateQuery->setParameter('dwlDateFrom', $dwlDateFrom);
        $inBetweenDateQuery->setParameter('dwlDateTo', $dwlDateTo);

        $beforeDateSettings = $beforeDateQuery->getResult();
        $inBetweenDateSettings = $inBetweenDateQuery->getResult();

        return array_merge($beforeDateSettings,$inBetweenDateSettings);
    }

    public function deleteMemberRevenueShare(int $memberId): void
    {
        $queryBuilder = $this->createQueryBuilder('revenue');
        $queryBuilder
            ->delete()
            ->where('revenue.member = :memberId')
            ->setParameter('memberId', $memberId);
        $queryBuilder->getQuery()->execute();
    }
}