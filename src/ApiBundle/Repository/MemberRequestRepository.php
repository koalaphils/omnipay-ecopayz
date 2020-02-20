<?php

namespace ApiBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use DbBundle\Entity\MemberRequest;
use DbBundle\Entity\Customer as Member;

class MemberRequestRepository
{
    private $em;
    private $entityClass;

    public function __construct(EntityManager $em, string $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }

    public function filters($filters, $orders = [], $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);

        $offset = array_get($filters, 'offset', 0);
        $limit = array_get($filters, 'limit', 20);

        foreach ($orders as $order) {
            $queryBuilder->addOrderBy('mrs.' . $order['column'], $order['dir']);
        }

        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function getTotal($filters, $hydrationMode = Query::HYDRATE_OBJECT): int
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->select('COUNT(mrs.id) total_requests');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    protected function createFilterQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mrs');
        $queryBuilder->leftJoin('mrs.member', 'm');
        $queryBuilder->leftJoin('m.user', 'u');

        if (array_has($filters, 'memberId')) {
            $queryBuilder->andWhere('mrs.member = :memberId');
            $queryBuilder->setParameter('memberId', $filters['memberId']);
        }

        if (array_has($filters, 'from')) {
            $queryBuilder->andWhere('mrs.date >= :from');
            $queryBuilder->setParameter('from', new \DateTime($filters['from']));
        }

        if (array_has($filters, 'to')) {
            $queryBuilder->andWhere('mrs.date < :to');
            $queryBuilder->setParameter('to', (new \DateTime($filters['to'] . '+1 day')));
        }
        
        if (array_has($filters, 'search')) {
            $expression = $queryBuilder->expr()->orX();
            $expression->add('mrs.number LIKE :search');
            $expression->add('u.username LIKE :search');
            $expression->add('m.fullName LIKE :search');
            $expression->add('u.email LIKE :search');
            
            $queryBuilder->andWhere($expression);
            $queryBuilder->setParameter('search', '%' . array_get($filters, 'search') . '%');
        }
        
        if (array_has($filters, 'types')) {
            $queryBuilder
                ->andWhere('mrs.type IN (:types)')
                ->setParameter('types', $filters['types']);
        }
        
        if (array_has($filters, 'status')) {
            $queryBuilder
                ->andWhere('mrs.status IN (:status)')
                ->setParameter('status', $filters['status']);
        }

        return $queryBuilder;
    }

    protected function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias, $indexBy);
    }
}
