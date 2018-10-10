<?php

namespace BrokerageBundle\Repository;

use DbBundle\Repository\BaseRepository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
/**
 * CustomerProductRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerProductRepository extends BaseRepository
{
    private $em;
    private $entityClass;

    public function __construct(EntityManager $em, string $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }

    public function findContainsPathBrokerageSyncId()
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->where("JSON_CONTAINS_PATH(cp.details, 'all', '$.brokerage.sync_id') = :brokerageSyncId")
            ->setParameter(':brokerageSyncId', 1)
            ->orderBy('cp.userName');

        return $qb->getQuery()->getResult();
    }
    

    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias, $indexBy);
    }
}
