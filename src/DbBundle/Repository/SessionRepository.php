<?php

namespace DbBundle\Repository;

//use Doctrine\ORM\EntityRepository;
//use Crm\DbBundle\Entity\Session;
use DbBundle\Entity\Session;

/**
 * SessionRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SessionRepository extends BaseRepository
{
    public function remove(Session $entity)
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    public function findBySessionToken($sessionKey)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('PARTIAL s.{id, sessionId, key, createdAt}, PARTIAL su.{id, type}');
        $qb->leftJoin('s.user', 'su');
        $qb->where('s.key = :key')->setParameter('key', $sessionKey);
        //$qb->andWhere('s.userType = :userType')->setParameter('userType', $userType);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findBySessionId(string $sessionId): ?Session
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->where('s.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function deleteUserSessionExcept(int $userId, array $exceptSessionId = []): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->delete(Session::class, 's');
        $qb
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
        ;

        if (!empty($exceptSessionId)) {
            $qb
                ->andWhere('s.sessionId NOT IN (:exceptSessions)')
                ->setParameter('exceptSessions', $exceptSessionId)
            ;
        }

        $qb->getQuery()->execute();
    }
}
