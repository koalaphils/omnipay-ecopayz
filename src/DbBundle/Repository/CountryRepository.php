<?php

namespace DbBundle\Repository;

/**
 * CountryRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CountryRepository extends BaseRepository
{
    public function getWithCurrency($id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c, cu');
        $qb->leftJoin('c.currency', 'cu')
            ->where('c.id = :currency')
            ->setParameter('currency', $id);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getCountryListQb($filters)
    {
        $qb = $this->createQueryBuilder('country');
        $qb->leftJoin('country.currency', 'currency');

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'country.name LIKE :search',
                'country.code LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['tags'])) {
            $json = json_encode($filters['tags']);
            $qb->andWhere('JSON_CONTAINS(country.tags, :tags) = 1')->setParameter('tags', $json);
        }

        return $qb;
    }

    public function getCountryList($filters = null, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_ARRAY)
    {
        $qb = $this->getCountryListQb($filters);
        $qb->select('country, currency');

        if (array_has($filters, 'length') || array_has($filters, 'limit')) {
            $qb->setMaxResults(array_get($filters, 'length', array_get($filters, 'limit', 20)));
        }
        if (array_has($filters, 'start') || array_has($filters, 'offset')) {
            $qb->setFirstResult(array_get($filters, 'start', array_get($filters, 'offset', 0)));
        }

        foreach (array_get($filters, 'order', []) as $order) {
            $qb->addOrderBy($order['column'], $order['dir']);
        }

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getCountryListFilterCount($filters = null)
    {
        $qb = $this->getCountryListQb($filters);
        $qb->select('COUNT(country.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCountryListAllCount()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByCode($code, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('PARTIAL c.{id, name, code}');
        $qb->where('c.code = :code')->setParameter('code', $code);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findByPhoneCode($code, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('PARTIAL c.{id, name, phoneCode, code}');
        $qb->where('c.phoneCode = :phone_code')->setParameter('phone_code', $code);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }
}
