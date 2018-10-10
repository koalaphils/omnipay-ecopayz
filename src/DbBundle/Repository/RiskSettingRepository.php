<?php

namespace DbBundle\Repository;

use Doctrine\ORM\AbstractQuery;

class RiskSettingRepository extends BaseRepository
{
    public function total(array $filters = []): int
    {
        $qb = $this->createFilterQb($filters);
        $qb->select('COUNT(rs.riskId) AS total_count');

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function createFilterQb(array $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('rs');
        return $qb;
    }

    public function filter(array $filters = [], array $orders = [], ?int $limit = 10, ?int $offset = 0, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT): array
    {
        $qb = $this->createFilterQb($filters);
        
        foreach ($orders as $order) {
            $qb->addOrderBy('rs.' . $order['column'], $order['dir']);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        if (isset($filters['isActive'])) {
            $qb->andWhere('rs.isActive = :isActive')
                ->setParameter('isActive', $filters['isActive'])
            ;
        }

        if (array_has($filters, 'search') && !empty($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'rs.resourceId LIKE :search',
            ]))->setParameter('search', '%' . str_replace("IDtag", "", $filters['search']) . '%');
        }

        $qb->addOrderBy('rs.id', 'DESC');
        $qb->andWhere('rs.isLatest = 1');

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function findLatest(string $resourceId)
    {
        return $this->createQueryBuilder('rs')
            ->where('rs.isLatest = 1')
            ->andWhere('rs.resourceId = :resourceId')
            ->setParameter('resourceId', $resourceId)
            ->getQuery()
            ->getSingleResult()
        ;
    }

}
