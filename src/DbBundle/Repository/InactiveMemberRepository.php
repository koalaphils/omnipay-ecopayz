<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Customer as Member;

use DbBundle\Entity\InactiveMember;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class InactiveMemberRepository extends BaseRepository
{
    public function findMembersWithNoActivityForPastSixMonths(): array
    {
        $currentDate = new \DateTimeImmutable();
        $oneHundredEightyDaysAgo = $currentDate->modify('-180 days');

        return $this->getMembersWithNoTransactionsForPastFewMonths($oneHundredEightyDaysAgo);
    }

    /**
     * @return Array customerIDs with
     *  ...0 products
     *  ...0 balance on all member products
     *  ...0 amount on ALL COMPLETED transactions (deposit,withdraw,bonus,transfer,p2p,dwl,bet)
     */
    public function getUsersWithZeroBalanceAndZeroTransactions(\DateTimeInterface $startDate): array
    {
        $this->doNotLoadEverythingIntoMemoryAllAtOnce();
        $sql = ' SELECT customerProduct.customerId as customerId,customerBalance 
                    FROM customer
                    LEFT JOIN (
                          SELECT cproduct_customer_id as customerId,SUM(cproduct_balance) as customerBalance
                          FROM customer_product
                          GROUP BY cproduct_customer_id
                        ) customerProduct ON customerProduct.customerId = customer_id
                    LEFT JOIN (
                          SELECT DISTINCT transaction_customer_id as customerId
                          FROM transaction 
                          WHERE transaction_date >= DATE(:sixMonthsAgo)
                          AND transaction_status = :completedTransaction
                        ) distinctTransactions ON distinctTransactions.customerId = customer_id
                    WHERE (customerBalance IS NULL OR customerBalance = 0)
                    AND distinctTransactions.customerId IS NULL';

        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('customerId','customerId');
        $resultsMap->addScalarResult('customerBalance','customerBalance');
        $query = $this->getEntityManager()->createNativeQuery($sql, $resultsMap);
        $query->setParameter('sixMonthsAgo', $startDate);
        $query->setParameter('completedTransaction', Transaction::TRANSACTION_STATUS_END);

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);

        $memberIdsWithZeroTransationsAndZeroBalance = [];
        foreach ($iterableResult as $key => $row) {
            $memberData = array_pop($row);
            $memberIdsWithZeroTransationsAndZeroBalance[] = $memberData;
        }
        $this->reEnabledBufferedQueries();

        return $memberIdsWithZeroTransationsAndZeroBalance;
    }

    /**
    * @return Array customerIDs with
    *  ...0 amount on ALL COMPLETED transactions (deposit,withdraw,bonus,transfer,p2p,dwl,bet)
    */
    public function getMembersWithNoTransactionsForPastFewMonths(\DateTimeInterface $startDate): array
    {
        $this->doNotLoadEverythingIntoMemoryAllAtOnce();
        $sql = ' SELECT customer_id as customerId 
                    FROM customer
                    LEFT JOIN (
                          SELECT DISTINCT transaction_customer_id as customerId
                          FROM transaction 
                          WHERE transaction_date >= DATE(:sixMonthsAgo)
                          AND transaction_status = :completedTransaction
                        ) distinctTransactions ON distinctTransactions.customerId = customer_id
                    WHERE distinctTransactions.customerId IS NULL 
                    ';

        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('customerId','customerId');
        $query = $this->getEntityManager()->createNativeQuery($sql, $resultsMap);
        $query->setParameter('sixMonthsAgo', $startDate);
        $query->setParameter('completedTransaction', Transaction::TRANSACTION_STATUS_END);

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);

        $memberIdsWithZeroTransations = [];
        foreach ($iterableResult as $key => $row) {
            $memberData = array_pop($row);
            $memberIdsWithZeroTransations[] = $memberData['customerId'];
        }
        $this->reEnabledBufferedQueries();

        return $memberIdsWithZeroTransations;
    }

    /**
     * this will prevent MySQL from loading everything into the memory
     * this will, instead, load each item as you traverse a result set
     */
    private function doNotLoadEverythingIntoMemoryAllAtOnce(): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    private function reEnabledBufferedQueries(): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * empty the table
     */
    public function clearList(): void
    {
        $this->getEntityManager()->createQuery('DELETE DbBundle:InactiveMember i')->execute();
        $tableName = $this->getEntityManager()->getClassMetadata('DbBundle:InactiveMember')->getTableName();
        $connection = $this->getEntityManager()->getConnection();
        $connection->exec("ALTER TABLE " . $tableName . " AUTO_INCREMENT = 1;");
    }

    /**
     * this is different from parent:save() because this one flushes a specific entity only and not ALL modified entities
     *
     * @param InactiveMember $inactiveMember
     */
    public function saveEntity(InactiveMember $inactiveMember)
    {
        $this->getEntityManager()->persist($inactiveMember);
        $this->getEntityManager()->flush($inactiveMember);
    }

    public function detach($entity)
    {
        $this->getEntityManager()->detach($entity);
        $this->getEntityManager()->flush($entity);
    }

    public function getInactiveMembersCount(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder('inactiveList');
        $qb->select('count(inactiveList.id)');
        $qb->from(InactiveMember::class, 'inactiveList');
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count ?? 0;
    }

}
