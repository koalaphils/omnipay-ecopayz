<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Customer;

class MarketingToolRepository extends BaseRepository
{
    public function findMarketingToolByMember(Customer $member): ?\DbBundle\Entity\MarketingTool
    {
        $queryBuilder = $this->createQueryBuilder('mt');
        $queryBuilder
            ->where('mt.member = :member and mt.isLatest = :isLatest')
            ->setParameters([
                'member' => $member->getId(),
                'isLatest' => true,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getAffiliateLinkPreviousUpdatesByMember($memberId):? array
    {
        $queryBuilder = $this->createQueryBuilder('mt');
        $queryBuilder
            ->where('mt.member = :member')
            ->setParameters([
                'member' => $memberId,
            ])
            ->setMaxResults(20)
            ->orderBy('mt.createdAt', 'DESC')
        ;

        return $queryBuilder->getQuery()->getScalarResult();
    }
}