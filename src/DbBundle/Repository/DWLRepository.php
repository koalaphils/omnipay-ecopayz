<?php

namespace DbBundle\Repository;

use DateTime;
use DateTimeInterface;
use DbBundle\Entity\DWL;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use function array_get;
use function array_has;
use function date_dbvalue;

/**
 * DWLRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DWLRepository extends BaseRepository
{
    /**
     * Get daily win loss list query builder.
     *
     * @param array $filters
     *
     * @return QueryBuilder
     */
    public function getListQb($filters)
    {
        $qb = $this->createQueryBuilder('d');

        if (array_has($filters, 'product')) {
            $qb->andWhere('d.product = :product')->setParameter('product', $filters['product']);
        }
        if (array_has($filters, 'currency')) {
            $qb->andWhere('d.currency = :currency')->setParameter('currency', $filters['currency']);
        }
        if (array_has($filters, 'from')) {
            $from = date_dbvalue($filters['from'], $filters['dateFormat'], $this->getEntityManager());
            $qb->andWhere('d.date >= :from')->setParameter('from', $from);
        }
        if (array_has($filters, 'to')) {
            $to = date_dbvalue($filters['to'], $filters['dateFormat'], $this->getEntityManager());
            $qb->andWhere('d.date <= :to')->setParameter('to', $to);
        }

        return $qb;
    }

    /**
     * @param array $filters
     * @param array $orders
     * @param array $selects
     * @param array $hydrationMode
     *
     * @return array
     */
    public function getList($filters = [], $orders = [], $selects = [], $hydrationMode = null)
    {
        $aliases = $this->getAliases();
        $qb = $this->getListQb($filters);
        $qb->select($this->getPartials($qb, 'd', $aliases, $selects));

        foreach ($orders as $order) {
            list($column, $dir) = explode(' ', trim(preg_replace('/\s+/', ' ', $order)));
            list($alias, $column) = explode('.', $column);
            $this->join($qb, $alias, $aliases);
            $qb->orderBy("$alias.$column", $dir);
        }

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select('COUNT(d.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount()
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('COUNT(d.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAliases($reverse = false)
    {
        if ($reverse) {
            return [
                '_main_' => 'd',
                'dwl.product' => ['p' => 'd', 'a' => 'p', 'added' => false],
                'dwl.currency' => ['p' => 'd', 'a' => 'c', 'added' => false],
            ];
        }

        return [
            'd' => ['main' => true, 'i' => 'id'],
            'p' => ['p' => 'd', 'c' => 'product', 'added' => false, 'i' => 'id'],
            'c' => ['p' => 'd', 'c' => 'currency', 'added' => false, 'i' => 'id'],
        ];
    }

    public function getAvailableFilters()
    {
        return ['length', 'start', 'search', 'product', 'currency', 'from', 'to', 'dateFormat'];
    }

    public function findDWL(
        array $filters = [],
        array $orders = [],
        int $limit = 20,
        int $offset = 0,
        array $select = [],
        int $hydrationMode = Query::HYDRATE_OBJECT
    ): array {
        $queryBuilder = $this->createFilterQueryBuilder($filters);

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);
        if (empty($select)) {
            $queryBuilder->select('dwl, currency, product');
        } else {
            $queryBuilder->select($select);
        }

        if (empty($orders)) {
            $queryBuilder->addOrderBy('dwl.date', 'desc');
        }

        foreach ($orders as $order) {
            $queryBuilder->addOrderBy($order['column'], $order['dir']);
        }

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function getTotal($filters = []): int
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->select('COUNT(dwl.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findDWLByDateProductAndCurrency(int $productId, int $currencyId, DateTime $dwlDate): ?DWL
    {
        $queryBuilder = $this->createQueryBuilder('dwl');
        $queryBuilder->where('dwl.product = :productId AND dwl.currency = :currencyId AND dwl.date = :dwlDate');
        $queryBuilder->setParameters([
            'productId' => $productId,
            'currencyId' => $currencyId,
            'dwlDate' => $dwlDate,
        ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getDWLIdsFromRange(
        int $productId,
        int $currencyId,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        $queryBuilder = $this->createQueryBuilder('dwl');
        $queryBuilder->select('dwl.id')
            ->where('dwl.product = :producId')
            ->andWhere('dwl.currency = :currencyId')
            ->andWhere('dwl.date >= :from')
            ->andWhere('dwl.date <= :to')
            ->orderBy('dwl.date', 'ACS')
            ->setParameters([
                'producId' => $productId,
                'currencyId' => $currencyId,
                'from' => $from,
                'to' => $to,
            ]);

        return $queryBuilder->getQuery()->getScalarResult();
    }

    protected function createFilterQueryBuilder(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('dwl');
        $queryBuilder
            ->join('dwl.product', 'product')
            ->join('dwl.currency', 'currency')
        ;

        if ($this->validFilter($filters, 'product')) {
            $queryBuilder->andWhere('product.id = :product');
            $queryBuilder->setParameter('product', $filters['product']);
        }

        if (!empty(array_get($filters, 'products', []))) {
            $queryBuilder->andWhere('product.id IN (:products)')->setParameter('products', $filters['products']);
        }

        if ($this->validFilter($filters, 'currency')) {
            $queryBuilder->andWhere('currency.id = :currency');
            $queryBuilder->setParameter('currency', $filters['currency']);
        }

        if (!empty(array_get($filters, 'currencies', []))) {
            $queryBuilder->andWhere('currency.id IN (:currencies)')->setParameter('currencies', $filters['currencies']);
        }

        if ($this->validFilter($filters, 'status')) {
            $queryBuilder->andWhere('dwl.status IN (:status)')->setParameter('status', $filters['status']);
        }

        if ($this->validFilter($filters, 'from')) {
            $queryBuilder->andWhere('dwl.date >= :from');
            $queryBuilder->setParameter('from', new DateTime($filters['from']));
        }

        if ($this->validFilter($filters, 'to', '')) {
            $queryBuilder->andWhere('dwl.date < :to');
            $queryBuilder->setParameter('to', (new DateTime($filters['to'] . '+1 day')));
        }

        return $queryBuilder;
    }

    protected function validFilter($filters, $name): bool
    {
        if (array_get($filters, $name, '') === '' || array_get($filters, $name, null) === null) {
            return false;
        }

        return true;
    }
}
