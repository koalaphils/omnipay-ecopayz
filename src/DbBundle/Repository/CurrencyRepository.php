<?php

namespace DbBundle\Repository;

/**
 * CurrencyRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CurrencyRepository extends BaseRepository
{
    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getCurrencyListQb($filters)
    {
        $qb = $this->createQueryBuilder('c');

        if (isset($filters['search'])) {
//            $qb->andWhere($qb->expr()->orX()->addMultiple([
//                'c.name LIKE :search',
//                'c.code LIKE :search',
//            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (array_has($filters, 'currencyNames')) {
            $currencyNames = array_get($filters, 'currencyNames');
            if (!is_array($currencyNames)) {
                $currencyNames = [$currencyNames];
            }
            $qb->andWhere('c.name IN (:currencyNames)');
            $qb->setParameter('currencyNames', $currencyNames);
        }

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {
            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('c.createdAt >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('c.createdAt < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }
        }

        return $qb;
    }

    public function getCurrencyList(array $filters = [], array $orders = []): array
    {
        $qb = $this->getCurrencyListQb($filters);
        $qb->leftJoin('c.updater', 'updater');
        $qb->select('PARTIAL c.{id, name, code, rate, updatedAt, createdAt}, PARTIAL updater.{id, username}');
        $qb->where("c.code = 'EUR'");

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

        return $qb->getQuery()->getArrayResult();
    }

    public function getCurrencyListFilterCount($filters = null)
    {
        $qb = $this->getCurrencyListQb($filters);
        $qb->select('COUNT(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCurrencyListAllCount()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param type $name
     * @param int  $hydrationMode
     *
     * @return type
     */
    public function findByName($name, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('PARTIAL c.{id, name, code, rate}');
        $qb->where('c.name = :name')->setParameter('name', $name);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findByCode($code, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('PARTIAL c.{id, name, code, rate}');
        $qb->where('c.code = :code')->setParameter('code', $code);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findById($id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('PARTIAL c.{id, name, code, rate}');
        $qb->where('c.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }
}
