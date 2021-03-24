<?php

namespace ApiBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use DbBundle\Collection\Collection;

class ProductRepository
{
    private $em;
    private $entityClass;

    public function __construct(EntityManager $em, string $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }

    public function findByCode($code, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('p.code = :code')->setParameter('code', $code);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function findByName($name, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('p.name = :name')->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function list($filters, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('product');
        $qb->where('product.deletedAt IS NULL');

        if (array_has($filters, 'is_active')) {
            $qb->andWhere('product.isActive = :isActive');
            $qb->setParameter('isActive', $filters['is_active']);
        }

        if (array_has($filters, 'exclude')) {
            $productCodes = explode(',', $filters['exclude']);

            $qb->andWhere('product.code NOT IN (:code)');
            $qb->setParameter('code', $productCodes);
        }

        $offset = array_get($filters, 'offset', 0);
        $limit = array_get($filters, 'limit', 20);

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getTotal($hydrationMode = Query::HYDRATE_OBJECT): int
    {
        $qb = $this->createQueryBuilder('product');
        $qb->select('COUNT(product.id) total_products');

        return $qb->getQuery()->getSingleScalarResult();
    }

    protected function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias, $indexBy);
    }
}
