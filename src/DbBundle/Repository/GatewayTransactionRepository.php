<?php

namespace DbBundle\Repository;

use Doctrine\ORM\Query;
use DbBundle\Entity\GatewayTransaction;

class GatewayTransactionRepository extends BaseRepository
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
        $qb = $this->createQueryBuilder('gt');
        $qb->join('gt.gateway', 'g');
        $qb->join('gt.currency', 'cur');

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('gt.date >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('gt.date < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }

            if (!empty(array_get($groupFilters, 'gateway', []))) {
                $qb->andWhere('g.id IN (:gateway)')->setParameter('gateway', $groupFilters['gateway']);
            }

            if (!empty(array_get($groupFilters, 'paymentOption', []))) {
                $qb->andWhere('gt.paymentOption IN (:paymentOption)')->setParameter('paymentOption', $groupFilters['paymentOption']);
            }

            if (!empty(array_get($groupFilters, 'type', []))) {
                $qb->andWhere('gt.type IN (:type)')->setParameter('type', $groupFilters['type']);
            }

            if (array_has($groupFilters, 'status')) {
                $exp = $qb->expr()->orX();
                if (in_array(GatewayTransaction::GATEWAY_TRANSACTION_STATUS_VOIDED, $groupFilters['status'])) {
                    $exp->add('gt.isVoided = true');
                }
                $exp->add('gt.status IN (:status) AND gt.isVoided = false');
                $qb->setParameter('status', $groupFilters['status']);
                $qb->andWhere($exp);
            }

            if (!empty(array_get($groupFilters, 'currency', []))) {
                $qb->andWhere('cur.id IN (:currency)')->setParameter('currency', $groupFilters['currency']);
            }

            if (!empty($groupFilters['search'])) {
                $search = array_get($groupFilters, 'search');
                $exp = $qb->expr()->orX();
                $qb->andWhere($exp->addMultiple([
                    "gt.number LIKE :search",
                    "g.name LIKE :search",
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

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select('COUNT(gt.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount()
    {
        $qb = $this->createQueryBuilder('gt');
        $qb->select('COUNT(gt.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
