<?php

declare(strict_types = 1);

namespace DbBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use TwoFactorBundle\Provider\Message\CodeModel;
use TwoFactorBundle\Provider\Message\Exceptions\CodeDoesNotExistsException;
use TwoFactorBundle\Provider\Message\StorageInterface;

class TwoFactorCodeRepository extends EntityRepository implements StorageInterface
{
    public function saveCode(CodeModel $model): void
    {
        $this->getEntityManager()->persist($model);
        $this->getEntityManager()->flush($model);
    }

    /**
     * @param string $code
     * @return CodeModel
     *
     * @throws CodeDoesNotExistsException
     * @throws NonUniqueResultException
     */
    public function getCode(string $code): CodeModel
    {
        $queryBuilder = $this->createQueryBuilder('f');
        $queryBuilder
            ->where('f.code = :code')
            ->setParameters(['code' => $code])
        ;
        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $ex) {
            throw new CodeDoesNotExistsException(sprintf('Code %s does not exists', $code));
        }
    }


    public function purgeExpiredIds(): void
    {
        $dateTime = new \DateTime('now');
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder->delete('DbBundle:TwoFactorCode', 'f')
            ->andWhere('f.expireAt < :datetime')
            ->setParameter('datetime', $dateTime)
            ->getQuery()
        ;
        $query->execute();
    }
}