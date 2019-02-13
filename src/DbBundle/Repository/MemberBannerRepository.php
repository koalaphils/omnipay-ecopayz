<?php

namespace DbBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class MemberBannerRepository extends BaseRepository
{
    public function findMemberBanners(array $filters = [], array $orders = [], int $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);

        $queryBuilder->select('mb', 'mw', 'mrn', 'bi', 'm')
            ->leftJoin('mb.memberWebsite', 'mw')
            ->join('mb.memberReferralName', 'mrn')
            ->join('mb.bannerImage', 'bi')
            ->join('mb.member', 'm');

        foreach ($orders as $column => $dir) {
            $queryBuilder->addOrderBy($column, $dir);
        }

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function countMemberBanners(array $filters = []): int
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);

        $queryBuilder->select('mb', 'mw', 'mrn', 'bi', 'm')
            ->leftJoin('mb.memberWebsite', 'mw')
            ->join('mb.memberReferralName', 'mrn')
            ->join('mb.bannerImage', 'bi')
            ->join('mb.member', 'm');

        $queryBuilder->select($queryBuilder->expr()->count('mb.id'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getCampaignNameList(array $filters = [], array $orders = [], int $limit = 10, int $offset = 0): array
    {
        $queryBuilder = $this->getFilteredCampaignNames($filters);
        $queryBuilder->select(
            'PARTIAL mb.{id, campaignName}'
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

    public function getCampaignNameListFilterCount(array $filters = []): int
    {
        $queryBuilder = $this->getFilteredCampaignNames($filters);
        $queryBuilder->select($queryBuilder->expr()->count('mb.id'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function createFilterQueryBuilder(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mb');

        if (array_get($filters, 'member', null) !== null) {
            $queryBuilder->where($queryBuilder->expr()->eq('m.id', ':memberId'))
                ->setParameter('memberId', array_get($filters, 'member'));
        }

        return $queryBuilder;
    }
    
    private function getFilteredCampaignNames(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mb');

        if (array_get($filters, 'member', null) !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mb.member', ':member'))
                ->setParameter('member', $filters['member'])
            ;
        }

        if (!empty($filters['search'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple([
                'mb.campaignName LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $queryBuilder;
    }

    public function getMemberReferralLinkOptions(int $memberId): array
    {
        $queryBuilder = $this->createQueryBuilder('mb');

        $queryBuilder->select('bi.type', 'bi.language', 'mrn.name AS trackingCode')
            ->join('mb.bannerImage', 'bi')
            ->join('mb.memberReferralName', 'mrn')
            ->join('mb.member', 'm', 'WITH', $queryBuilder->expr()->eq('m.id', ':memberId'))
            ->setParameter('memberId', $memberId);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
