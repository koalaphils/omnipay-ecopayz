<?php

namespace TransactionBundle\Repository;

use Doctrine\ORM\Query;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class TransactionRepository
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getTransactions(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilderForTransaction();
        $queryBuilder
            ->where('transaction.status = :status AND transaction.isVoided = 0')
            ->setParameter('status', $filters['status']);
        ;

        if (!empty($filters['interval'])) {
            $queryBuilder->
                andWhere('transaction.updatedAt <= :interval')
                ->setParameter('interval', new \DateTime("-" . $filters['interval']))
            ;
        }
        
        if (!empty($filters['type'])) {
            $queryBuilder->
                andWhere('transaction.type = :type')
                ->setParameter('type', $filters['type'])
            ;
        }

        if (!empty($filters['paymentOptionType'])) {
            $queryBuilder->
                andWhere('transaction.paymentOptionType IN (:paymentOptionType)')
                ->setParameter('paymentOptionType', $filters['paymentOptionType'])
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function createQueryBuilderForTransaction(): QueryBuilder
    {
        return $this
            ->entityManager
            ->createQueryBuilder()
            ->select('transaction')
            ->from(Transaction::class, 'transaction')
        ;
    }
}
