<?php

namespace ApiBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use DbBundle\Collection\Collection;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\CustomerProduct;

class TransactionRepository
{
    private $em;
    private $entityClass;

    public function __construct(EntityManager $em, string $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }

    public function findByIdAndCustomer($id, $customer, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.id = :id')->setParameter('id', $id);
        $qb->andWhere('t.customer = :customer')->setParameter('customer', $customer);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function filters($filters, $orders = [], $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT): array
    {
        $qb = $this->createFilterQb($filters);

        $offset = array_get($filters, 'offset', 0);
        $limit = array_get($filters, 'limit', 20);

        foreach ($orders as $order) {
            $qb->addOrderBy('t.' . $order['column'], $order['dir']);
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getTotal($filters, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT): int
    {
        $qb = $this->createFilterQb($filters);
        $qb->select('COUNT(t.id) total_transcations');

        return $qb->getQuery()->getSingleScalarResult();
    }

    protected function createFilterQb($filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        if (array_has($filters, 'customer')) {
            $qb->andWhere('t.customer = :customer');
            $qb->orWhere('t.toCustomer = :customer');
            $qb->setParameter('customer', $filters['customer']);
        }

        if (array_has($filters, 'from')) {
            $qb->andWhere('t.date >= :from');
            $qb->setParameter('from', new \DateTime($filters['from']));
        }

        if (array_has($filters, 'to')) {
            $qb->andWhere('t.date < :to');
            $qb->setParameter('to', (new \DateTime($filters['to'] . '+1 day')));
        }
        
        if (array_has($filters, 'search')) {
            $exp = $qb->expr()->orX();
            $exp->add('t.number LIKE :search');
            if (!array_has($filters, 'customer')) {
                $qb->leftJoin('t.customer', 'c');

                $qb->leftJoin('c.user', 'u');
                $exp->add('u.username LIKE :search');
                $exp->add('c.fName LIKE :search');
                $exp->add('c.mName LIKE :search');
                $exp->add('c.lName LIKE :search');
                $exp->add('c.email LIKE :search');
            }
            
            $exp->add('(SELECT COUNT(sb.id) FROM ' . SubTransaction::class . ' AS sb INNER JOIN sb.customerProduct AS sbc WHERE sb.parent = t AND sbc.userName LIKE :search) > 0');

            $qb->andWhere($exp);
            $qb->setParameter('search', '%' . array_get($filters, 'search') . '%');
        }
        
        if (array_has($filters, 'types')) {
            $qb->andWhere('t.type IN (:types)')->setParameter('types', $filters['types']);
        } else {
            $qb->andWhere('t.type NOT IN (:types)')
                ->setParameter('types', [Transaction::TRANSACTION_TYPE_BET, Transaction::TRANSACTION_TYPE_DWL])
            ;
        }
        
        if (array_has($filters, 'status')) {
            $qb->andWhere('t.status IN (:status)')->setParameter('status', $filters['status']);
        }
        
        if (array_has($filters, 'paymentOption')) {
            $qb->join('t.paymentOption', 'po');
            $exp = $qb->expr()->orX();
            $exp
                ->add('t.paymentOptionType IN (:paymentOption)')
                ->add('po.paymentOption IN (:paymentOption)')
            ;
            $qb->andWhere($exp)->setParameter('paymentOption', $filters['paymentOption']);
        }

        if (array_has($filters, 'interval')) {
            $qb->andWhere('t.date <= CURRENT_TIMESTAMP() AND t.date >= :interval');
            $qb->setParameter('interval', new \DateTime("-" . $filters['interval']));
        }
      
        return $qb;
    }

    protected function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias, $indexBy);
    }
}
