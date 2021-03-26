<?php

namespace DbBundle\Repository;

use DbBundle\Entity\MemberWebsite;

class MemberWebsiteRepository extends BaseRepository
{
    public function findOneByWebsite(string $website): ?MemberWebsite
    {
        $queryBuilder = $this->createQueryBuilder('mw');

        $queryBuilder->select('mw')
            ->where($queryBuilder->expr()->eq('mw.website', ':website'))
            ->setParameter('website', $website);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getActiveCount(int $memberId): int
    {
        $queryBuilder = $this->createQueryBuilder('mw');

        $queryBuilder->select($queryBuilder->expr()->count('mw.id'))
            ->join('mw.member', 'm', 'WITH', $queryBuilder->expr()->eq('m.id', ':memberId'))
            ->where($queryBuilder->expr()->eq('mw.isActive', ':isActive'))
            ->setParameters([
               'memberId' => $memberId,
               'isActive' => true,
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findWebsites(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createFilterQuery($filters);
        $queryBuilder->select('mw');

        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getTotalWebsite(array $filters = []): int
    {
        $queryBuilder = $this->createFilterQuery($filters);
        $queryBuilder->select($queryBuilder->expr()->count('mw.id'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function createFilterQuery(array $filters = []): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mw');
        if (array_has($filters, 'member')) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('mw.member', ':member'))
                ->setParameter('member', $filters['member']);
        }

        if (array_has($filters, 'search')) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('mw.website', ':search'))
                ->setParameter('search', $filters['search'] . '%');
        }

        return $queryBuilder;
    }
}
