<?php

namespace DbBundle\Repository;

use Doctrine\ORM\AbstractQuery;

class MemberTagRepository extends BaseRepository
{
    public function getListQb($filters)
    {
        $qb = $this->createQueryBuilder('mt');

        if (array_has($filters, 'search')) {
            $qb->andWhere('mt.name LIKE :search')->setParameter('search', '%' . $filters['search'] . '%');
        }

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');

            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('mt.createdAt >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('mt.createdAt < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }
        }

        return $qb;
    }

    public function getList($filters = [], $orders = [], $selects = [], $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $aliases = $this->getAliases();
        $qb = $this->getListQb($filters);
        $qb->select($this->getPartials($qb, 'mt', $aliases, $selects));

        foreach ($orders as $order) {
            list($column, $dir) = explode(' ', trim(preg_replace('/\s+/', ' ', $order)));
            list($alias, $column) = explode('.', $column);
            $this->join($qb, $alias, $aliases);
            $qb->orderBy("$alias.$column", $dir);
        }

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }
        $query = $qb->getQuery();

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select('COUNT(mt.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount()
    {
        $qb = $this->createQueryBuilder('mt');
        $qb->select('COUNT(mt.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAvailableFilters()
    {
        return ['length', 'start', 'search', 'name', 'filter'];
    }

    public function getAliases($reverse = false)
    {
        if ($reverse) {
            return [
                '_main_' => 'mt',
            ];
        }

        return [
            'mt' => ['main' => true, 'i' => 'id', 'must' => ['name']],
        ];
    }
}
