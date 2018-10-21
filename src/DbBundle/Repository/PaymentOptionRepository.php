<?php

namespace DbBundle\Repository;

/**
 * PaymentOptionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaymentOptionRepository extends BaseRepository
{
    public function filter(array $filters = [], array $orders = [], ?int $limit = 20, ?int $offset = 0, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT): array
    {
        $qb = $this->createFilterQb($filters);

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $qb->addOrderBy('po.' . $order['column'], $order['dir']);
            }
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult($hydrationMode);
    }
    
    public function total(array $filters = []): int
    {
        $qb = $this->createFilterQb($filters);
        $qb->select('COUNT(po.code) AS total_count');
        
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    private function createFilterQb(array $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('po');

        if (array_has($filters, 'is_active')) {
            $qb->andWhere('po.isActive = :isActive');
            $qb->setParameter('isActive', $filters['is_active']);
        }
        if (array_has($filters, 'search')) {
            $qb->andWhere('(po.code LIKE :search OR po.name LIKE :search)');
            $qb->setParameter('search', '%' . $filters['search'] . '%');
        }
        if (array_has($filters, 'code')) {
            $qb->andWhere('po.code LIKE :code')->setParameter('code', '%' . $filters['code'] . '%');
        }
        if (array_has($filters, 'name')) {
            $qb->andWhere('po.name LIKE :name')->setParameter('name', '%' . $filters['name'] . '%');
        }
        if (array_has($filters, 'status')) {
            $qb->andWhere('po.isActive IN (:status)')->setParameter('status', $filters['status']);
        }
        
        return $qb;
    }

    public function getPaymentOptionByCodes($paymentOptionCodes)
    {
        $qb = $this->createQueryBuilder('po');

        $qb->select('PARTIAL po.{code, name}')
            ->where($qb->expr()->in('po.code', ':paymentOptionCodes'))
            ->setParameter('paymentOptionCodes', $paymentOptionCodes);

        return $qb->getQuery()->getArrayResult();
    }

    public function getAutoDeclineTag()
    {
        $qb = $this->createQueryBuilder('po');

        $qb->select('PARTIAL po.{code}')
            ->where('po.autoDecline = :hasAutoDecline')
            ->setParameter('hasAutoDecline', 1);

        return $qb->getQuery()->getArrayResult();
    }
}