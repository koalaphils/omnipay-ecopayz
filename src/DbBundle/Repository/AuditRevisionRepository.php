<?php

namespace DbBundle\Repository;

use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\User;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Connection;

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

    public function getListSubQueryBuilder(?array $filters = null, $orders = []) : \Doctrine\DBAL\Query\QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $queryBuilder->select("audit_revision_id, audit_revision_user_id, audit_revision_timestamp, audit_revision_client_ip");
        $queryBuilder->from('audit_revision', 'ar');
        if (!empty(array_get($filters, 'userIds', []))) {
            $queryBuilder->andWhere('ar.audit_revision_user_id IN (:users)');
            $queryBuilder->setParameter('users', $filters['userIds'], Connection::PARAM_INT_ARRAY);
        }

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $queryBuilder->andWhere('ar.audit_revision_timestamp >= :from');
                $queryBuilder->setParameter('from', (new \DateTimeImmutable($groupFilters['from'])), \Doctrine\DBAL\Types\Type::DATETIME_IMMUTABLE);
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $queryBuilder->andWhere('ar.audit_revision_timestamp < :to');
                $queryBuilder->setParameter('to', (new \DateTimeImmutable($groupFilters['to'] . '+1 day')), \Doctrine\DBAL\Types\Type::DATETIME_IMMUTABLE);
            }

            if (!empty(array_get($groupFilters, 'operation', [])) || !empty(array_get($groupFilters, 'category', []))) {
                if (!empty($groupFilters['operation'])) {
                    $queryBuilder->andWhere('(SELECT COUNT(arl.audit_revision_log_id) FROM audit_revision_log AS arl WHERE ar.audit_revision_id = arl.audit_revision_log_audit_revision_id AND arl.audit_revision_log_operation IN (:operation)) > 0');
                    $queryBuilder->setParameter('operation', $groupFilters['operation'], Connection::PARAM_INT_ARRAY);
                }
                if (!empty($groupFilters['category'])) {
                    $queryBuilder->andWhere('(SELECT COUNT(arl2.audit_revision_log_id) FROM audit_revision_log AS arl2 WHERE ar.audit_revision_id = arl2.audit_revision_log_audit_revision_id AND arl2.audit_revision_log_category IN (:category)) > 0');
                    $queryBuilder->setParameter('category', $groupFilters['category'], Connection::PARAM_INT_ARRAY);
                }
            }

            if (!empty($groupFilters['search'])) {
                $exp = $queryBuilder->expr()->orX();
                $queryBuilder->andWhere($exp->addMultiple([
                    "(
                      SELECT COUNT(u.user_id) from user u
                      WHERE u.user_id = ar.audit_revision_user_id
                      AND u.user_username LIKE :search
                    ) > 0",
                    "ar.audit_revision_client_ip LIKE :search",
                    "(
                        SELECT COUNT(arl3.audit_revision_log_id) FROM audit_revision_log AS arl3 
                        WHERE ar.audit_revision_id = arl3.audit_revision_log_audit_revision_id 
                        AND (
                          arl3.audit_revision_log_label LIKE :search
                          OR IFNULL(JSON_SEARCH(arl3.audit_revision_log_details, 'all', :search), '0') <> '0'  
                        )
                     ) > 0"
                ]));
                $queryBuilder->setParameter('search', '%' . $groupFilters['search'] . '%');
            }
        }

        return $queryBuilder;
    }

    public function getListQb($filters = null, $orders = [])
    {
        $queryBuilder = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subQueryBuilder = $this->getListSubQueryBuilder($filters, $orders);

        if (isset($filters['start'])) {
            $subQueryBuilder->setFirstResult($filters['start']);
        }

        if (isset($filters['length'])) {
            $subQueryBuilder->setMaxResults($filters['length']);
        }

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $subQueryBuilder->addOrderBy($order['column'], $order['dir']);
            }
        }

        if (!empty(array_get($filters, 'userIds', []))) {
            $queryBuilder->setParameter('users', $filters['userIds'], Connection::PARAM_INT_ARRAY);
        }

        $groupFilters = array_get($filters, 'filter', []);

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $queryBuilder->setParameter('from', (new \DateTime($groupFilters['from'])), \Doctrine\DBAL\Types\Type::DATETIME);
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $queryBuilder->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')), \Doctrine\DBAL\Types\Type::DATETIME);
            }

            if (!empty(array_get($groupFilters, 'operation', [])) || !empty(array_get($groupFilters, 'category', []))) {
                if (!empty($groupFilters['operation'])) {
                    $queryBuilder->setParameter('operation', $groupFilters['operation'], Connection::PARAM_INT_ARRAY);
                }
                if (!empty($groupFilters['category'])) {
                    $queryBuilder->setParameter('category', $groupFilters['category'], Connection::PARAM_INT_ARRAY);
                }
            }
        }

        $queryBuilder
            ->select("ar.audit_revision_id")
            ->from("( " . $subQueryBuilder->getSql() . ")", "ar")
        ;

        if (!empty($groupFilters)) {
            if (!empty($groupFilters['search'])) {
                $queryBuilder->setParameter('search', '%' . $groupFilters['search'] . '%');
            }
        }

        return $queryBuilder;
    }

    public function getList($filters = null, $orders = [], $hydrationMode = Query::HYDRATE_ARRAY)
    {
        $getListQb = $this->getListQb($filters, $orders);
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $getListQb->addOrderBy($order['column'], $order['dir']);
            }
        }

        if (array_has($filters, 'length')) {
            $getListQb->setMaxResults($filters['length']);
        }

        $listArIds = $getListQb->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $arIds = [];
        foreach ($listArIds as $value) {
            $arIds[] = $value["audit_revision_id"];
        }

        $qb = $this->createQueryBuilder('ar');
        $qb->andWhere('ar.id IN (:arid)')->setParameter('arid',$arIds);

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $qb->addOrderBy($order['columnQB'], $order['dir']);
            }
        }

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListSubQueryBuilder($filters);
        $qb->select('COUNT(ar.audit_revision_id) as filterCount');
        if (!empty(array_get($filters, 'userIds', []))) {
            $qb->setParameter('users', $filters['userIds'], Connection::PARAM_INT_ARRAY);
        }

        $listFilterCount = $qb->execute()->fetch(\PDO::FETCH_ASSOC);
        $filterCount = array_get($listFilterCount, 'filterCount');

        return $filterCount;
    }

    public function getListAllCount($filters = null)
    {
        $qb = $this->createQueryBuilder('ar');
        $qb->select('COUNT(ar.id)');

        if (!empty(array_get($filters, 'userIds', []))) {
            $qb->andWhere('ar.user IN (:arid)')->setParameter('arid', $filters['userIds'], Connection::PARAM_INT_ARRAY);
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
    
    public function getLastAuditLogFor(string $identifier, string $class, int $category): ?AuditRevisionLog
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()->from(AuditRevisionLog::class, 'log');
        $queryBuilder
            ->select('log', 'revision')
            ->innerJoin('log.auditRevision', 'revision')
            ->where($queryBuilder->expr()->andX()->addMultiple([
                "log.identifier = :identifier",
                "log.className = :className",
                "log.category = :category",
            ]))
            ->orderBy('revision.id', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'identifier' => $identifier,
                'className' => $class,
                'category' => $category,
            ]);
        
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
    
    public function getAuditLogsForDWLSubtransactionIdentifier(
        SubTransaction $subTransaction,
        \DateTimeInterface $dateStart,
        \DateTimeInterface $dateEnd
    ): IterableResult {
        $this->setToBuffered();
        $transaction = $subTransaction->getParent();
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()->from(AuditRevisionLog::class, 'log');
        $expSubTransaction = $queryBuilder->expr()->andX()->addMultiple([
            "log.identifier = :subTransactionId",
            "log.className = :subTransactionClassName",
            "log.category = :memberDWLCategory",
        ]);
        $expMemberProduct = $queryBuilder->expr()->andX()->addMultiple([
            "log.identifier = :memberProductId",
            "log.className = :memberProductClassName",
            "log.category = :memberProductCategory",
            "log.operation = :updateOperation",
        ]);
        
        $queryBuilder
            ->select('log', 'revision')
            ->innerJoin('log.auditRevision', 'revision')
            ->where($queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->orX()->addMultiple([$expSubTransaction, $expMemberProduct]),
                'revision.timestamp >= :dateStart',
                'revision.timestamp <= :dateEnd',
            ]))
            ->orderBy('revision.id', 'ASC')
            ->setParameters([
                'subTransactionId' => (string) $subTransaction->getId(),
                'subTransactionClassName' => SubTransaction::class,
                'memberDWLCategory' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL,
                'memberProductId' => (string) $subTransaction->getCustomerProduct()->getId(),
                'memberProductClassName' => CustomerProduct::class,
                'memberProductCategory' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT,
                'updateOperation' => AuditRevisionLog::OPERATION_UPDATE,
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ]);
        
        return $queryBuilder->getQuery()->iterate();
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
