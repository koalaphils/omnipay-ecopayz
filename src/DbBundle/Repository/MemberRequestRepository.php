<?php

namespace DbBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use \DateTime;
use DbBundle\Entity\MemberRequest;
use DbBundle\Entity\Customer as Member;

class MemberRequestRepository extends BaseRepository
{

    public function getRequestList($filters = null, array $orders = []): array
    {
        $queryBuilder = $this->getRequestListQueryBuilder($filters);

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

        return $queryBuilder->getQuery()->getResult();
    }
    
    public function getRequestListQueryBuilder(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mrs');
        $queryBuilder->join('mrs.member', 'm');

        if (!empty(array_get($filters, 'filter', ''))) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX()->addMultiple([
                    'mrs.number LIKE :search',
                    'mrs.type = :search',
                    'm.fullName LIKE :search',]))
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $groupFilters = array_get($filters, 'filter', []);
        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'include_status', []))) {
                $queryBuilder
                    ->andWhere('mrs.status IN (:includeStatuses)')
                    ->setParameter('includeStatuses', array_keys($groupFilters['include_status']));
            }

            if (!empty(array_get($groupFilters, 'status', []))) {
                $queryBuilder
                    ->andWhere('mrs.status IN (:status)')
                    ->setParameter('status', $groupFilters['status']);
            }

            if (!empty(array_get($groupFilters, 'types', []))) {
                $queryBuilder
                    ->andWhere('mrs.type IN (:types)')
                    ->setParameter('types', $groupFilters['types']);
            }

            if (!empty(array_get($groupFilters, 'from', ''))) {
                $queryBuilder
                    ->andWhere('mrs.createdAt >= :from')
                    ->setParameter('from', new DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $queryBuilder
                    ->andWhere('mrs.createdAt < :to')
                    ->setParameter('to', (new DateTime($groupFilters['to'] . '+1 day')));
            }
        }

        return $queryBuilder;
    }

    public function getRequestListFilterCount($filters = null): int
    {
        $queryBuilder = $this->getRequestListQueryBuilder($filters);
        $queryBuilder->select('COUNT(mrs.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getRequestListAllCount(): int
    {
        $queryBuilder = $this->createQueryBuilder('mrs');
        $queryBuilder->select('COUNT(mrs.id)');
        

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findByIdAndType($memberRequestId, $requestTypeId): MemberRequest
    {
        $queryBuilder = $this->createQueryBuilder('mrs');
        $queryBuilder->where('mrs.id = :id AND mrs.type = :type')
            ->setParameter('id', $memberRequestId)
            ->setParameter('type', $requestTypeId);
        
        return $queryBuilder->getQuery()->getSingleResult(Query::HYDRATE_OBJECT);
    }

    public function migrateProductPassword(\DbBundle\Entity\Transaction $transaction): array
    {
        $returnedIds = [];
        $memberRequestProductPassword = new MemberRequest();
        $memberRequestProductPassword->setMember($transaction->getCustomer());
        $memberRequestProductPassword->setNumber($transaction->getNumber());
        $memberRequestProductPassword->setDate($transaction->getDate());
        if ($transaction->isVoided()) {
            $memberRequestProductPassword->setStatus(MemberRequest::MEMBER_REQUEST_STATUS_DECLINE);
        } else {
            $memberRequestProductPassword->setStatus($transaction->getStatus());
        }
        $memberRequestProductPassword->setType(MemberRequest::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD);
        $memberRequestProductPassword->setCreatedBy($transaction->getCreator());
        $memberRequestProductPassword->setCreatedAt($transaction->getCreatedAt());
        
        $i = 0;
        foreach ($transaction->getSubTransactions() as $subTransaction) {
            $returnedIds[$transaction->getId()][] = $subTransaction->getId();
            $productPassword = $subTransaction->getDetail('product_password');
            $memberRequestProductPassword->setProductPasswordEntry($i, $productPassword);
            $i++;
        }
        
        $this->save($memberRequestProductPassword);

        return $returnedIds;
    }

    public function getTotalProcessedRequestsByMember(Member $member): int
    {
        $queryBuilder = $this->createQueryBuilder('memberRequest');

        $queryBuilder->select('COUNT(memberRequest.id)')
            ->join('memberRequest.member', 'm', Join::WITH, 'm.id = :memberId')
            ->where('memberRequest.status = :processed')
            ->setParameter('memberId', $member->getId())
            ->setParameter('processed', MemberRequest::MEMBER_REQUEST_STATUS_END);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
