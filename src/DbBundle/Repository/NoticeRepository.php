<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Notice;

/**
 * NoticeRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NoticeRepository extends BaseRepository
{
    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getNoticeListQb($filters)
    {
        $qb = $this->createQueryBuilder('n');

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'n.title LIKE :search',
                'n.description LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb;
    }

    public function getNoticeList($filters = null)
    {
        $qb = $this->getNoticeListQb($filters);

        $qb->select('PARTIAL n.{id, title, description, type, startAt, endAt, isActive}');

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getNoticeListFilterCount($filters = null)
    {
        $qb = $this->getNoticeListQb($filters);
        $qb->select('COUNT(n.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNoticeListAllCount()
    {
        $qb = $this->createQueryBuilder('n');
        $qb->select('COUNT(n.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function delete(Notice $notice)
    {
        $this->getEntityManager()->remove($notice);
        $this->getEntityManager()->flush();
    }
}
