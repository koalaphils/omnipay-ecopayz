<?php

namespace DbBundle\Repository;

use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\User;
use Doctrine\ORM\Query;

class AuditRevisionRepository extends BaseRepository
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
        $qb = $this->createQueryBuilder('ar');

        $qb->join('ar.user', 'u');

        if (!empty(array_get($filters, 'type', []))) {
            $type = $filters['type'] == AuditRevision::TYPE_MEMBER ? User::USER_TYPE_MEMBER : User::USER_TYPE_ADMIN;
            $qb->andWhere('u.type = :type')->setParameter('type', $type);
        }

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('ar.timestamp >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('ar.timestamp < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }

            if (!empty(array_get($groupFilters, 'operation', [])) || !empty(array_get($groupFilters, 'category', []))) {
                if (!empty($groupFilters['operation'])) {
                    $qb->andWhere('(SELECT COUNT(arl.id) FROM ' . AuditRevisionLog::class . ' AS arl WHERE ar.id = arl.auditRevision AND arl.operation IN (:operation)) > 0')
                        ->setParameter('operation', $groupFilters['operation']);
                }
                if (!empty($groupFilters['category'])) {
                    $qb->andWhere('(SELECT COUNT(arl2.id) FROM ' . AuditRevisionLog::class . ' AS arl2 WHERE ar.id = arl2.auditRevision AND arl2.category IN (:category)) > 0')
                        ->setParameter('category', $groupFilters['category']);
                }
            }
            
            if (!empty($groupFilters['search'])) {
                $exp = $qb->expr()->orX();
                $qb->andWhere($exp->addMultiple([
                    "u.username LIKE :search",
                    "ar.clientIp LIKE :search",
                    "(SELECT COUNT(arl3.id) FROM " . AuditRevisionLog::class . " AS arl3 WHERE ar.id = arl3.auditRevision AND JSON_EXTRACT(arl3.details, '$.label') LIKE :search ) > 0"
                ]))->setParameter('search', '%' . $groupFilters['search'] . '%');
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
        $qb->select('COUNT(ar.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount($filters = null)
    {
        $qb = $this->createQueryBuilder('ar');
        $qb->select('COUNT(ar.id)');

        if (array_has($filters, 'type')) {
            $type = $filters['type'] == AuditRevision::TYPE_MEMBER ? User::USER_TYPE_MEMBER : User::USER_TYPE_ADMIN;

            $qb->join('ar.user', 'u')
                ->where($qb->expr()->eq('u.type', ':type'))
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getHistoryIPList($filters = [], $orders = []): array
    {
        $queryBuilder = $this->getHistoryIPListQb($filters);
        $queryBuilder->select(''
            . 'PARTIAL ar.{id, timestamp, clientIp}'
        );

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $queryBuilder->addOrderBy($order['column'], $order['dir']);
            }
        }
        
        if (isset($filters['length'])) {
            $queryBuilder->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $queryBuilder->setFirstResult($filters['start']);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    protected function getHistoryIPListQb(array $filters = []):  \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ar');

        $queryBuilder
            ->join('ar.user', 'u')
            ->leftJoin('ar.logs', 'l');

        if (!empty(array_get($filters, 'id', []))) {
            $queryBuilder->andWhere('u.id = :id');
            $queryBuilder->setParameter('id', $filters['id']);
        }

        if (!empty(array_get($filters, 'type', []))) {
            $queryBuilder->andWhere('u.type = :type');
            $queryBuilder->setParameter('type', $filters['type']);
        }

        if (!empty(array_get($filters, 'userId', []))) {
            $queryBuilder->andWhere('u.id = :userId');
            $queryBuilder->setParameter('userId', $filters['userId']);
        }

        if (!empty(array_get($filters, 'category', []))) {
            $queryBuilder->andWhere('l.category = :category');
            $queryBuilder->setParameter('category', $filters['category']);
        }

        if (!empty(array_get($filters, 'operation', []))) {
            $queryBuilder->andWhere('l.operation = :operation');
            $queryBuilder->setParameter('operation', $filters['operation']);
        }

        return $queryBuilder;
    }
}
