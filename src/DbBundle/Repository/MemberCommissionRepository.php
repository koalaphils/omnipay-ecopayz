<?php

namespace DbBundle\Repository;

use DateTimeInterface;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberCommission;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * This class should be named as MemberCommissionPercentageRepository
 */
class MemberCommissionRepository extends BaseRepository
{
    public function findCommissionByResourceId(string $resourceId): ?MemberCommission
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder
            ->where('commission.resourceId = :resourceId and commission.isLatest = :isLatest')
            ->setParameters([
                'resourceId' => $resourceId,
                'isLatest' => true,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByMemberId(int $memberId): array
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder->where('commission.member = :memberId AND commission.isLatest = :isLatest');
        $queryBuilder->setParameters([
            'memberId' => $memberId,
            'isLatest' => true,
        ]);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByMemberIdAndProductId(int $memberId, int $productId): ?MemberCommission
    {
        $queryBuilder = $this->createQueryBuilder('commission');
        $queryBuilder->where('commission.member = :memberId AND commission.product = :productId AND commission.isLatest = :isLatest');
        $queryBuilder->setParameters([
            'memberId' => $memberId,
            'productId' => $productId,
            'isLatest' => true,
        ]);
        $result = $queryBuilder->getQuery()->getOneOrNullResult();
        if ($result instanceof MemberCommission) {
            return $result;
        }

        return null;
    }

    public function findCommissions(array $filters = [], array $orders = [], int $limit = 20, int $offset = 0, int $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->leftJoin('commission.product', 'product');
        $queryBuilder->select('commission, product');

        if (!empty($orders)) {
            foreach ($orders as $column => $dir) {
                $queryBuilder->addOrderBy($column, $dir);
            }
        } else {
            $queryBuilder->addOrderBy("commission.createdAt", "desc");
        }

        if (array_get($filters, "includeOffsetAndLimit", true) === true) {
            $queryBuilder->setMaxResults($limit);
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function countCommissions(array $filters = []): int
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->select('COUNT(commission) AS commissionTotal');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findCommissionOfCustomerForProductBeforeOrOnDate(
        Member $member,
        Product $product,
        DateTimeInterface $forDate
    ): ?MemberCommission {
        $queryBuilder = $this->createFilterQueryBuilder([
            'member' => $member->getId(),
            'product' => $product->getId(),
            'beforeOrEqual' => $forDate]
        );
        $queryBuilder->orderBy('commission.createdAt', 'DESC')->addOrderBy('commission.id', 'DESC');
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function createFilterQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('commission');

        if (array_get($filters, 'member', null) !== null) {
            $queryBuilder->andWhere('commission.member = :memberId');
            $queryBuilder->setParameter('memberId', array_get($filters, 'member'));
        }

        if (array_get($filters, 'status', null) !== null) {
            $queryBuilder->andWhere('commission.status = :status');
            $queryBuilder->setParameter('status', array_get($filters, 'status'));
        }

        if (array_get($filters, 'product', null) !== null) {
            $queryBuilder
                ->andWhere('commission.product = :productId')
                ->setParameter('productId', array_get($filters, 'product'))
            ;
        }

        if (array_get($filters, 'beforeOrEqual',  null) !== null) {
            $queryBuilder
                ->andWhere('commission.createdAt <= :beforeOrEqual')
                ->setParameter('beforeOrEqual', array_get($filters, 'beforeOrEqual'))
            ;
        }

        if (array_get($filters, 'latest', null) !== null) {
            $queryBuilder->andWhere('commission.isLatest = :isLatest')->setParameter('isLatest', array_get($filters, 'latest', true));
        }

        return $queryBuilder;
    }
}
