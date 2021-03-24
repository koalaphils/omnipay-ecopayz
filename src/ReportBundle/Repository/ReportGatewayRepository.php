<?php

namespace ReportBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Query\QueryBuilder;
use DbBundle\Entity\Transaction;

class ReportGatewayRepository
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function report($filters = []): array
    {
        $currency = array_get($filters, 'currency', '');
        $from = array_get($filters, 'from', '');
        $to = array_get($filters, 'to', '');
        $gateways = array_get($filters, 'gateways', []);

        $query = $this->createQueryBuilder();
        $query->select(
            'gateway.gateway_id',
            'gateway.gateway_name',
            'COUNT(IF(transaction.transaction_type = :depositType, 1, NULL)) num_deposits',
            'COUNT(IF(transaction.transaction_type = :withdrawType, 1, NULL)) num_withdraws',
            'SUM('
                . 'IF('
                    . 'transaction.transaction_type = :depositType,'
                    . 'CAST(JSON_EXTRACT(transaction.transaction_other_details, "$.paymentGateway.computed_amount") AS DECIMAL(65,10)), CAST(0 AS DECIMAL(65,10))'
                . ')'
            . ') sum_deposits',
            'SUM('
                . 'IF('
                    . 'transaction.transaction_type = :withdrawType,'
                    . 'CAST(JSON_EXTRACT(transaction.transaction_other_details, "$.paymentGateway.computed_amount") AS DECIMAL(65,10)), CAST(0 AS DECIMAL(65,10))'
                . ')'
            . ') sum_withdraws',
            'SUM(CAST(IFNULL(JSON_EXTRACT(transaction.transaction_fees, "$.total_company_fee"), 0) AS DECIMAL(65,10))) sum_company_fees',
            'SUM(CAST(IFNULL(JSON_EXTRACT(transaction.transaction_fees, "$.total_customer_fee"),0) AS DECIMAL(65,10))) sum_customer_fees'
        );
        $query->from('gateway', 'gateway');

        $transactionJoinCondition = 'transaction.transaction_gateway_id = gateway.gateway_id '
            . 'AND transaction.transaction_type IN (:transactionTypes) '
            . 'AND transaction.transaction_status = :status AND transaction.transaction_is_voided = :isVoided';

        if ($from != '') {
            $transactionJoinCondition .= ' AND transaction.transaction_date >= :from';
            $query->setParameter('from', $from . ' 00:00:00');
        }

        if ($to != '') {
            $transactionJoinCondition .= ' AND transaction.transaction_date <= :to';
            $query->setParameter('to', $to . ' 23:59:59');
        }

        $query->leftJoin('gateway', 'transaction', 'transaction', $transactionJoinCondition);

        $query->setParameter('isVoided', false);
        $query->setParameter('withdrawType', Transaction::TRANSACTION_TYPE_WITHDRAW);
        $query->setParameter('depositType', Transaction::TRANSACTION_TYPE_DEPOSIT);
        $query->setParameter('transactionTypes', [Transaction::TRANSACTION_TYPE_WITHDRAW, Transaction::TRANSACTION_TYPE_DEPOSIT], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        $query->setParameter('status', Transaction::TRANSACTION_STATUS_END);

        if ($currency != '') {
            $query->andWhere('gateway.gateway_currency_id = :currency')->setParameter('currency', $currency);
        }

        if (!empty($gateways)) {
            $query->andWhere('gateway.gateway_id IN (:gateways)')->setParameter('gateways', $gateways, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        $query->groupBy('gateway.gateway_id');
        $query->orderBy('gateway.gateway_id', 'asc');

        $stmt = $query->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->doctrine->getConnection()->createQueryBuilder();
    }
}
