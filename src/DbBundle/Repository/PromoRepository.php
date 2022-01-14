<?php

namespace DbBundle\Repository;

use Doctrine\ORM\Query;

class PromoRepository extends BaseRepository
{
	public function findById($id, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function getPromoListQb($filters)
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'p.code LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status');
			$qb->setParameter('status', $filters['status']);
        }

        return $qb;
    }

    public function getPromoList($filters = null, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->getPromoListQb($filters);

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function findByCode($filters = null, $hydrationMode = Query::HYDRATE_ARRAY)
    {
        $qb = $this->getPromoListQb($filters);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }
}