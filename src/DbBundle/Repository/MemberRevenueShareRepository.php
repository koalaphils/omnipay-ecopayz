<?php

namespace DbBundle\Repository;

use DateTimeInterface;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberRevenueShare;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

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

        if (array_get($filters, 'latest', null) !== null) {
            $queryBuilder->andWhere('revenue.isLatest = :isLatest')->setParameter('isLatest', array_get($filters, 'latest', true));
        }

        return $queryBuilder;
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