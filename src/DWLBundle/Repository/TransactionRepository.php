<?php

namespace DWLBundle\Repository;

use Doctrine\ORM\Query;
use DbBundle\Entity\SubTransaction;
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

    public function getSubtransactionsByDWLId(int $dwlId, ?int $limit = 100, ?int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilderForSubtransaction();
        $queryBuilder
            ->join('subtransaction.parent', 'transaction')
            ->where('(JSON_EXTRACT(subtransaction.details, :path) = :id OR JSON_EXTRACT(subtransaction.details, :path) = :id_string)')
            ->setParameters([
                'id' => $dwlId,
                'id_string' => (string) $dwlId,
                'path' => '$.dwl.id',
            ])
        ;
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getSubtransactionsByDWLIteratable(int $dwlId): \Doctrine\ORM\Internal\Hydration\IterableResult
    {
        $this->setToBuffered();

        $queryBuilder = $this->createQueryBuilderForSubtransaction();
        $queryBuilder
            ->join('subtransaction.parent', 'transaction')
            ->where("subtransaction.dwlId = :dwlId")
            ->setParameters([
                'dwlId' => $dwlId,
            ])
        ;

        return $queryBuilder->getQuery()->iterate();
    }

    public function getSubtransactionByProductAndDwlId(int $customerProductId, int $dwlId): ?SubTransaction
    {
        $queryBuilder = $this->createQueryBuilderForSubtransaction();
        $queryBuilder
            ->join('subtransaction.parent', 'transaction')
            ->where('subtransaction.dwlId = :dwlId')
            ->andWhere('subtransaction.customerProduct = :customerProductId')
            ->setParameters([
                'dwlId' => $dwlId,
                'customerProductId' => $customerProductId,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByNotInIds(array $transactionIds, int $dwlId): array
    {
        $qb = $this->createQueryBuilderForTransaction();
        $qb->join('transaction.customer', 'customer');

        $qb->select('transaction,customer');
        $qb
            ->where('transaction.id NOT IN (:ids) AND transaction.dwlId = :dwlId')
            ->setParameter('ids', $transactionIds)
            ->setParameter('dwlId', $dwlId)
        ;

        return $qb->getQuery()->getResult();
    }

    public function deleteTransactionNotInIds(\DbBundle\Entity\DWL $dwl, array $transactionIds)
    {
        $this->deleteSubtransactionNotInIds($dwl, $transactionIds);
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->delete(Transaction::class, 'transaction')
            ->where('(JSON_EXTRACT(transaction.details, :path) = :dwl_id OR JSON_EXTRACT(transaction.details, :path) = :dwlid_string)')
            ->andWhere('transaction.id NOT IN (:transacton_ids)')
            ->setParameters([
                'transacton_ids' => $transactionIds,
                'path' => '$.dwl.id',
                'dwlid_string' => (string) $dwl->getId(),
                'dwl_id' => $dwl->getId(),
            ])
        ;

        return $queryBuilder->getQuery()->execute();
    }

    public function getDWLByCustomerProduct($customerProductId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilderForSubtransaction();
        $queryBuilder
            ->select('subtransaction, dwl')
            ->from(SubTransaction::class, 'subtransaction')
            ->innerJoin('subtransaction', 'dwl');
        ;
    }

    private function deleteSubtransactionNotInIds(\DbBundle\Entity\DWL $dwl, array $transactionIds)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->delete(SubTransaction::class, 'subtransaction')
            ->where('(JSON_EXTRACT(subtransaction.details, :path) = :dwl_id OR JSON_EXTRACT(subtransaction.details, :path) = :dwlid_string)')
            ->andWhere('subtransaction.parent NOT IN (:transacton_ids)')
            ->setParameters([
                'transacton_ids' => $transactionIds,
                'path' => '$.dwl.id',
                'dwlid_string' => (string) $dwl->getId(),
                'dwl_id' => $dwl->getId(),
            ])
        ;

        $queryBuilder->getQuery()->execute();
    }

    private function createQueryBuilderForSubtransaction(): QueryBuilder
    {
        return $this
            ->entityManager
            ->createQueryBuilder()
            ->select('subtransaction')
            ->from(SubTransaction::class, 'subtransaction')
        ;
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

    private function setToBuffered(): void
    {
        $this->entityManager
            ->getConnection()
            ->getWrappedConnection()
            ->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
}
