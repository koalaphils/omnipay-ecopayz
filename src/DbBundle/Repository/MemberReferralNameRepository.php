<?php

namespace DbBundle\Repository;

use DbBundle\Entity\MemberReferralName;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class MemberReferralNameRepository extends BaseRepository
{
    public function findOneByName(string $name): ?MemberReferralName
    {
        $queryBuilder = $this->createQueryBuilder('mrn');

        $queryBuilder->select('mrn')
            ->where($queryBuilder->expr()->eq('mrn.name', ':name'))
            ->setParameter('name', $name);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getActiveCount(int $memberId): int
    {
        $queryBuilder = $this->createQueryBuilder('mrn');

        $queryBuilder->select($queryBuilder->expr()->count('mrn.id'))
            ->join('mrn.member', 'm', 'WITH', $queryBuilder->expr()->eq('m.id', ':memberId'))
            ->where($queryBuilder->expr()->eq('mrn.isActive', ':isActive'))
            ->setParameters([
               'memberId' => $memberId,
               'isActive' => true,
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getReferralNameList(array $filters = [], array $orders = [], int $limit = 10, int $offset = 0): array
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select(
            'PARTIAL mrn.{id, name, isActive}'
        );

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $queryBuilder->addOrderBy($order['column'], $order['dir']);
            }
        }

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }

    public function getReferralNameListFilterCount(array $filters = []): int
    {
        $queryBuilder = $this->getFilteredReferralNames($filters);
        $queryBuilder->select($queryBuilder->expr()->count('mrn.id'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getReferralNameActiveCount(int $memberId): int
    {
        $queryBuilder = $this->createQueryBuilder('mrn');

        $queryBuilder->select($queryBuilder->expr()->count('mrn.id'))
            ->join('mrn.member', 'm', 'WITH', $queryBuilder->expr()->eq('m.id', ':memberId'))
            ->where($queryBuilder->expr()->eq('mrn.isActive', ':isActive'))
            ->setParameters([
               'memberId' => $memberId,
               'isActive' => true,
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getFilteredReferralNames(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mrn');

        if (!empty($filters['member'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mrn.member', ':member'))
                ->setParameter('member', $filters['member'])
            ;
        }

        if (!empty($filters['search'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple([
                'mrn.name LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $queryBuilder;
    }
}
