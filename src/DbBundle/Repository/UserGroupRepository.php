<?php

namespace DbBundle\Repository;

/**
 * UserGroupRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserGroupRepository extends BaseRepository
{
    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getGroupListQb($filters)
    {
        $qb = $this->createQueryBuilder('g');

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'g.name LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb;
    }

    public function getGroupList($filters = null, $orders = [])
    {
        $qb = $this->getGroupListQb($filters);
        $qb->select('PARTIAL g.{id, name, roles}');

        foreach ($orders as $order) {
            $qb->addOrderBy('g.' . $order['column'], $order['dir']);
        }

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getGroupListFilterCount($filters = null)
    {
        $qb = $this->getGroupListQb($filters);
        $qb->select('COUNT(g.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getGroupListAllCount()
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('COUNT(g.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
