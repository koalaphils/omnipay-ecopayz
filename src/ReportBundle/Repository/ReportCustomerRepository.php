<?php

namespace ReportBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Description of ReportCustomerRepository
 *
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class ReportCustomerRepository
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getCustomerWithBalance(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->select(
                'customer_id',
                'customer_fname',
                'customer_lname',
                'customer_full_name',
                'currency_id',
                'currency_code',

                'GROUP_CONCAT(cproduct_id) AS customer_product_ids',
                'IFNULL(SUM(cproduct_balance),0) AS customer_current_balance'
            )
            ->from('customer', 'c')
            ->innerJoin('c', 'currency', 'cu', 'c.customer_currency_id = cu.currency_id')
            ->leftJoin('c', 'customer_product', 'cp', 'cp.cproduct_customer_id = c.customer_id')
            ->groupBy('c.customer_id')
            ->orderBy('customer_id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;

        if (($filters['currency'] ?? '') !== '') {
            $queryBuilder->andWhere('customer_currency_id = :currency');
            $queryBuilder->setParameter('currency', $filters['currency']);
        }

        if (($filters['customer'] ?? '') !== '') {
            $queryBuilder->andWhere('customer_id = :customer');
            $queryBuilder->setParameter('customer', $filters['customer']);
        }

        if (($filters['customer_search'] ?? '') !== '') {
            $queryBuilder->andWhere(
                "(CONCAT(customer_fname, ' ', customer_lname) LIKE :customer_search"
                . " OR customer_fname LIKE :customer_search"
                . " OR customer_lname LIKE :customer_search"
                . " OR customer_full_name LIKE :customer_search)"
            );
            $queryBuilder->setParameter('customer_search', $filters['customer_search'] . '%');
        }

        return $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countCustomers(array $filters = []): int
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->select(
                'COUNT(customer_id) as total'
            )
            ->from('customer', 'c')
        ;

        if (($filters['currency'] ?? '') !== '') {
            $queryBuilder->andWhere('customer_currency_id = :currency');
            $queryBuilder->setParameter('currency', $filters['currency']);
        }

        if (($filters['customer_search'] ?? '') !== '') {
            $queryBuilder->andWhere('customer_fname LIKE :customer_search OR customer_lname LIKE :customer_search OR customer_full_name LIKE :customer_search');
            $queryBuilder->setParameter('customer_search', $filters['customer_search'] . '%');
        }

        return (int) $queryBuilder->execute()->fetchColumn();
    }

    public function getCustomerProductReport($filters = [], int $limit = 20, int $offset = 0, array $orders = [], array $groups = [], array $selects = [])
    {
        $queryBuilder = $this->createQueryBuilder();

        if (!empty($filters['products'] ?? [])) {
            $queryBuilder->andWhere('cp.cproduct_product_id IN (:products)');
            $queryBuilder->setParameter('products', $filters['products'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        if (!empty($filters['customerProductIds'] ?? [])) {
            $queryBuilder->andWhere('cp.cproduct_id IN (:customerProductIds)');
            $queryBuilder->setParameter('customerProductIds', $filters['customerProductIds'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        $customerJoinCondition = $queryBuilder->expr()->andX();
        $customerJoinCondition->add('c.customer_id = cp.cproduct_customer_id');
        if (($filters['currency'] ?? '') !== '') {
            $customerJoinCondition->add('c.customer_currency_id = :currency');
            $queryBuilder->setParameter('currency', $filters['currency']);
        }

        if (array_has($filters, 'customer')) {
            $customerJoinCondition->add('cp.cproduct_customer_id = :customer');
            $queryBuilder->setParameter('customer', $filters['customer']);
        }

        if (array_has($filters, 'search')) {
            $queryBuilder->andWhere('cp.cproduct_username LIKE :search');
            $queryBuilder->setParameter('search', $filters['search'] . '%');
        }

        $queryBuilder->select(
            'SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.turnover") AS DECIMAL(65, 10)), 0)) turnover',
            'SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.winLoss") AS DECIMAL(65, 10)), 0)) win_loss',
            'SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.gross") AS DECIMAL(65, 10)), 0)) gross',
            'SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.commission") AS DECIMAL(65, 10)), 0)) commission',
            'SUM(IFNULL(CAST(st.subtransaction_amount AS DECIMAL(65, 10)), 0)) amount'
        );

        $queryBuilder->addSelect($selects);

        $queryBuilder->from('customer_product', 'cp');
        $queryBuilder->join('cp', 'product', 'p', 'cp.cproduct_product_id = p.product_id');
        $queryBuilder->innerJoin('cp', 'customer', 'c', $customerJoinCondition);
        $queryBuilder->leftJoin('c', 'currency', 'cu', 'cu.currency_id = c.customer_currency_id');
        $queryBuilder->leftJoin(
            'cp',
            '(sub_transaction st INNER JOIN dwl ON '
                . 'dwl.dwl_id = st.subtransaction_dwl_id'
                . ' AND dwl.dwl_date >= :from'
                . ' AND dwl.dwl_date <= :to'
                . ' AND dwl.dwl_status = :dwlCompleted'
            . ')',
            '',
            'st.subtransaction_customer_product_id = cp.cproduct_id'
        );

        $queryBuilder->groupBy((empty($groups) ? 'cp.cproduct_id' : $groups));
        if (empty($orders)) {
            $queryBuilder->orderBy('cp.cproduct_id');
        } else {
            foreach ($orders as $column => $dir) {
                $queryBuilder->addOrderBy($column, $dir);
            }
        }

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
            $queryBuilder->setFirstResult($offset);
        }

        $queryBuilder->setParameter('from', $filters['from']);
        $queryBuilder->setParameter('to', $filters['to']);
        $queryBuilder->setParameter('dwlCompleted', \DbBundle\Entity\DWL::DWL_STATUS_COMPLETED);

        return $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function computeCustomerProductsTotalTransactions(
        array $filters,
        string $from = null,
        string $to = null,
        ?array $groups = [],
        array $orders = [],
        array $select = []
    ) {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select(
            'IFNULL(SUM( CAST( IF ( st.subtransaction_type = :deposit, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0) deposit',
            'IFNULL(SUM( CAST( IF ( st.subtransaction_type = :withdraw, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0) withdraw',
            'IFNULL(SUM( CAST( IF ( st.subtransaction_type = :dwl, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0) winloss',
            'IFNULL(SUM( CAST( IF ( (st.subtransaction_type = :bet AND JSON_CONTAINS(st.subtransaction_details, :betSettled) = 1), IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0) bet',
            'IFNULL(SUM( CAST( IF ( st.subtransaction_type = :deposit, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0)'
            . ' - IFNULL(SUM( CAST( IF ( st.subtransaction_type = :withdraw, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0)'
            . ' + IFNULL(SUM( CAST( IF ( st.subtransaction_type = :dwl, IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0)'
            . ' - IFNULL(SUM( CAST( IF ( (st.subtransaction_type = :bet AND JSON_CONTAINS(st.subtransaction_details, :betSettled) = 1), IFNULL(st.subtransaction_amount, 0), 0 ) AS DECIMAL (65, 10))), 0) total'
        );

        if (!empty($select)) {
            $queryBuilder->addSelect($select);
        }

        $customerJoinCondition = $queryBuilder->expr()->andX();
        $customerJoinCondition->add('c.customer_id = cp.cproduct_customer_id');
        if (($filters['currency'] ?? '') !== '') {
            $customerJoinCondition->add('c.customer_currency_id = :currency');
            $queryBuilder->setParameter('currency', $filters['currency']);
        } elseif (array_has($filters, 'currencies') && !empty($filters['currencies'])) {
            $customerJoinCondition->add('c.customer_currency_id IN (:currencies)');
            $queryBuilder->setParameter('currencies', $filters['currencies'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }
        if (array_has($filters, 'customer') && $filters['customer'] !== '') {
            $customerJoinCondition->add('c.customer_id = :customer');
            $queryBuilder->setParameter('customer', $filters['customer']);
        }

        $customerProductJoinCondition = $queryBuilder->expr()->andX();
        $customerProductJoinCondition->add('st.subtransaction_customer_product_id = cp.cproduct_id');
        if (array_has($filters, 'products') && !empty($filters['products'])) {
            $customerProductJoinCondition->add('cp.cproduct_product_id IN (:products)');
            $queryBuilder->setParameter('products', $filters['products'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        $queryBuilder->from('sub_transaction', 'st');
        $queryBuilder->innerJoin('st', 'customer_product', 'cp', $customerProductJoinCondition);
        $queryBuilder->innerJoin('cp', 'customer', 'c', $customerJoinCondition);
        $queryBuilder->innerJoin('st', 'transaction', 't', 't.transaction_id = st.subtransaction_transaction_id AND t.transaction_status = :completed AND t.transaction_is_voided = :isVoided');
        if (!empty($filters['customerProductIds'])) {
            $queryBuilder->andWhere('st.subtransaction_customer_product_id IN (:cproductIds)');
            $queryBuilder->setParameter('cproductIds', $filters['customerProductIds'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        if ($from !== null) {
            $queryBuilder->andWhere('t.transaction_date >= :from');
            $queryBuilder->setParameter('from', $from);
        }

        if ($to !== null) {
            $queryBuilder->andWhere('t.transaction_date <= :to');
            $queryBuilder->setParameter('to', $to);
        }

        if (empty($groups)) {
            $queryBuilder->groupBy('st.subtransaction_customer_product_id');
        } elseif (array_search('all', $groups) === false) {
            $queryBuilder->groupBy($groups);
        }

        if (!empty($orders)) {
            foreach ($orders as $column => $dir) {
                $queryBuilder->addOrderBy($column, $dir);
            }
        }

        $queryBuilder->setParameter('deposit', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_DEPOSIT);
        $queryBuilder->setParameter('withdraw', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_WITHDRAW);
        $queryBuilder->setParameter('dwl', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_DWL);
        $queryBuilder->setParameter('bet', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_BET);
        $queryBuilder->setParameter('betSettled', '{"betSettled": false}');
        $queryBuilder->setParameter('completed', \DbBundle\Entity\Transaction::TRANSACTION_STATUS_END);
        $queryBuilder->setParameter('isVoided', false);

        $result = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->doctrine->getConnection()->createQueryBuilder();
    }
}
