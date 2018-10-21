<?php

namespace DbBundle\Repository;

use DbBundle\Entity\User;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends BaseRepository implements \Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface
{
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->select('u, g')
            ->leftJoin('u.group', 'g')
            ->where(
                '(u.username = :login OR u.email = :login) AND u.deletedAt IS NULL AND u.type = '
                . User::USER_TYPE_ADMIN
            )
            ->setParameter('login', $username)
            ->getQuery()->getOneOrNullResult();
    }

    public function getUserByZendeskId($id)
    {
        return $this->createQueryBuilder('u')
                ->select('u')
                ->where('u.zendeskId = :id  AND u.deletedAt IS NULL AND u.zendeskId IS NOT NULL')
                ->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult();
    }

    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getUserListQb($filters)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->leftjoin('u.group', 'ug');
        $qb->leftjoin('u.creator', 'cr');

        if (isset($filters['isActive'])) {
            $qb->andWhere($qb->expr()->andX('u.isActive = :isActive'))->setParameter('isActive', $filters['isActive']);
        }

        if (isset($filters['types'])) {
            if (!is_array($filters['types'])) {
                $filters['types'] = [$filters['types']];
            }
            $qb->andWhere($qb->expr()->in('u.type', $filters['types']));
        }

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'u.username LIKE :search',
                'u.email LIKE :search',
                'ug.name LIKE :search',
                'cr.username LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['notNull'])) {
            $qb->andWhere($qb->expr()->isNotNull('u.zendeskId'));
        }

        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        if (!empty($groupFilters)) {

            if (!empty(array_get($groupFilters, 'status', []))) {
                $qb->andWhere('u.isActive IN (:status)')->setParameter('status', $groupFilters['status']);
            }

            if (!empty(array_get($groupFilters, 'group', []))) {
                $qb->andWhere('ug.id IN (:group)')->setParameter('group', $groupFilters['group']);
            }

            if (!empty(array_get($groupFilters, 'from', ''))) {
                $qb->andWhere('u.createdAt >= :from');
                $qb->setParameter('from', new \DateTime($groupFilters['from']));
            }

            if (!empty(array_get($groupFilters, 'to', ''))) {
                $qb->andWhere('u.createdAt < :to');
                $qb->setParameter('to', (new \DateTime($groupFilters['to'] . '+1 day')));
            }
        }

        $qb->andWhere($qb->expr()->andX('u.deletedAt IS NULL'));

        return $qb;
    }

    public function getUserList($filters = null, $orders = [])
    {
        $qb = $this->getUserListQb($filters);
        $qb->select(
            'PARTIAL u.{id, username, email, isActive, type, zendeskId, createdAt, preferences}'
            . ', PARTIAL cr.{id, username}'
            . ', PARTIAL ug.{id, name}'
        );
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

    public function getUserListFilterCount($filters = null)
    {
        $qb = $this->getUserListQb($filters);
        $qb->select('COUNT(u.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getUserListAllCount()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $qb->where($qb->expr()->andX('u.deletedAt IS NULL'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAdminCount() : int
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $qb->andWhere($qb->expr()->andX('u.type = :admin'));
        $qb->setParameter('admin', User::USER_TYPE_ADMIN);
        $qb->andWhere($qb->expr()->andX('u.deletedAt IS NULL'));
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function updateUserPreference($key = null, $params = [])
    {
        $status = false;
        if (empty($key)) {
            return $status;
        }
        /* //should be like these
        $param = array(
            'key' => 'isRead',
            'id' => 'id' or NULL
        );
        */

        $qb = $this->createQueryBuilder('u');
        $qb->update('DbBundle:User', 'u')
            ->set('u.preferences', 'JSON_REMOVE(u.preferences, :key)')
            ->setParameter('key', ['$.' . $key]);

        if (isset($params['id'])) {
            $qb->where($qb->expr()->eq('u.id', ':id'))
             ->setParameter('id', $params['id']);
        }

        $qb->getQuery()->execute();

        $status = true;

        return $status;
    }

    /**
     * @param User   $user
     * @param string $channel
     * @param int    $num
     *
     * @return mixed
     */
    public function updateCounter($user, $channel, $num)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->update('DbBundle:User', 'u')
            ->set('u.preferences', 'JSON_SET(u.preferences, :key, :count)')
            ->where($qb->expr()->eq('u.id', ':userId'))
            ->setParameter('key', sprintf('$.counters.%s', $channel))
            ->setParameter('count', $num)
            ->setParameter('userId', $user->getId());

        return $qb->getQuery()->execute();
    }

    public function findByActivationCode($activationCode)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->where($qb->expr()->eq('u.activationCode', ':activationCode'))
            ->setParameter('activationCode', $activationCode);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByUsername($username, $type)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->where($qb->expr()->eq('u.username', ':username'))
            ->andWhere($qb->expr()->eq('u.type', ':type'))
            ->setParameter('username', $username)
            ->setParameter('type', $type);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAdminByUsername($username): ?User
    {
        return $this->findByUsername($username, User::USER_TYPE_ADMIN);
    }

    public function findByEmail($email, $type, $isActivated = true)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->where($qb->expr()->eq('u.email', ':email'))
            ->andWhere($qb->expr()->eq('u.type', ':type'))
            ->setParameter('email', $email)
            ->setParameter('type', $type);

        if ($isActivated) {
            $qb->andWhere($qb->expr()->isNotNull('u.activationTimestamp'));
        }

        return $qb->getQuery()->getOneOrNullResult();
    }


    public function findByEmailAndResetPasswordCode($resetPasswordCode, $email)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->where($qb->expr()->eq('u.resetPasswordCode', ':resetPasswordCode'))
            ->andWhere($qb->expr()->eq('u.email', ':email'))
            ->andWhere($qb->expr()->isNotNull('u.activationTimestamp'))
            ->setParameter('resetPasswordCode', $resetPasswordCode)
            ->setParameter('email', $email);

        return $qb->getQuery()->getOneOrNullResult();
    }
}