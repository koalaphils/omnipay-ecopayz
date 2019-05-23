<?php

namespace DbBundle\Repository;

use DbBundle\Entity\GatewayLog;
use Doctrine\ORM\Query;

class GatewayLogRepository extends BaseRepository
{
    public function save($entity)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    public function getListQb($filters)
    {
        $qb = $this->createQueryBuilder('gl');
        $qb->join('gl.gateway', 'g');
        $qb->leftJoin('gl.currency', 'cur');

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('gl.timestamp >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('gl.timestamp < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }

            if (!empty(array_get($groupFilters, 'currency', []))) {
                $qb->andWhere('cur.id IN (:currency)')->setParameter('currency', $groupFilters['currency']);
            }

            if (!empty(array_get($groupFilters, 'gateway', []))) {
                $qb->andWhere('g.id IN (:gateway)')->setParameter('gateway', $groupFilters['gateway']);
            }

            if (!empty($groupFilters['search'])) {
                $search = array_get($groupFilters, 'search');
                $exp = $qb->expr()->orX();
                $qb->andWhere($exp->addMultiple([
                    'gl.referenceNumber LIKE :search',
                    'g.name LIKE :search',
                ]))->setParameter('search', '%' . $search . '%');
            }
        }

        return $qb;
    }

    public function getList($filters = null, $orders = [], $hydrationMode = Query::HYDRATE_ARRAY)
    {
        $qb = $this->getListQb($filters);

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $qb->addOrderBy($order['column'], $order['dir']);
            }
        }

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }

        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function findLastGatewayLogByIdentifierAndClass(string $class, string $identifier): ?GatewayLog
    {
        $qb = $this->createQueryBuilder('g');
        $qb
            ->where('g.referenceIdentifier = :identifier')
            ->andWhere('g.referenceClass = :class')
            ->setMaxResults(1)
            ->orderBy('g.timestamp', 'desc')
            ->setParameters([
                'identifier' => $identifier,
                'class' => $class,
            ])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select('COUNT(gl.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount()
    {
        $qb = $this->createQueryBuilder('gl');
        $qb->select('COUNT(gl.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
