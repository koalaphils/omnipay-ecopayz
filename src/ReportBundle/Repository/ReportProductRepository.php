<?php

namespace ReportBundle\Repository;

use DbBundle\Entity\CustomerProduct;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Query\QueryBuilder;

class ReportProductRepository
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function report($filters = [])
    {
        $currency = array_get($filters, 'currency', '');
        $from = array_get($filters, 'from', '');
        $to = array_get($filters, 'to', '');
        $products = array_get($filters, 'products', []);
        $search = array_get($filters, 'search', '');
        $apiUserIds = $this->getApiUserIds();
        $apiUserIds = array_map(function ($userId) {
            return $userId['user_id'];
        }, $apiUserIds);

        $signUps = $this->getSignUps($currency, $from, $to, $apiUserIds, $products, $search);

        return $signUps;
    }

    public function getSignUps(string $currency, string $from, string $to, array $apiUserIds, array $products, $search)
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select(
            'product.product_id',
            'product.product_name',
            'COUNT(cproduct.cproduct_id) num_sign_ups',
            'COUNT(IF(cproduct.total_trans > 0, cproduct.cproduct_id, NULL)) num_new_accounts',
            'COUNT(IF(cproduct.total_trans = 0, cproduct.cproduct_id, NULL)) num_signups_wo_deposit',
            'IFNULL(dwlf.totalTurnover, 0) turnover',
            'IFNULL(dwlf.totalWinLoss, 0) win_loss',
            'IFNULL(dwlf.totalGrossComm, 0) gross_commission',
            'IFNULL(dwlf.totalCommission, 0) commission',
            'ct.total_product total_register',
            'sts.num_active_accounts num_active_accounts'
        );

        $customerProductJoinConditions = [
            'customer.customer_id = cpp.cproduct_customer_id',
            'customer.customer_currency_id = :currency',
        ];

        if (!empty($products)) {
            $customerProductJoinConditions[] = 'cpp.cproduct_product_id IN (:products)';
            $queryBuilder->where('product.product_id IN (:products)');
            $queryBuilder->setParameter('products', $products, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        if ($from !== '') {
            $customerProductJoinConditions[] = 'cpp.cproduct_created_at >= :from';
            $queryBuilder->setParameter('from', $from . ' 00:00:00');
        }

        if ($to !== '') {
            $customerProductJoinConditions[] = 'cpp.cproduct_created_at <= :to';
            $queryBuilder->setParameter('to', $to . ' 23:59:59');
        }

        if ($search !== '') {
            $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple([
                    'product.product_name LIKE :search',
                ])
            )->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->from(
            'product LEFT JOIN'
            . '(SELECT '
            . 'cpp.cproduct_id, '
            . 'cpp.cproduct_customer_id, '
            . 'cpp.cproduct_product_id, '
            . 'cpp.cproduct_created_by,'
            . 'COUNT(IF(st.subtransaction_type = :deposit, st.subtransaction_id, NULL)) total_trans'
            . ' FROM customer_product cpp'
            . ' INNER JOIN customer ON '
            . implode(' AND ', $customerProductJoinConditions)
            . ' LEFT JOIN (sub_transaction st INNER JOIN transaction t ON t.transaction_id = st.subtransaction_transaction_id)'
            . ' ON st.subtransaction_customer_product_id = cpp.cproduct_id'
            . ' WHERE cpp.cproduct_created_by IN (:userIds)'
            . ' GROUP BY cpp.cproduct_id'
            . ') cproduct'
            . ' ON product.product_id = cproduct.cproduct_product_id'
            . ' LEFT JOIN (
                SELECT IF ( count(d.dwl_id) = 0, 0, SUM(CAST(JSON_EXTRACT(d.dwl_details, "$.total.turnover")  AS DECIMAL(65,10)))) totalTurnover,
                    IF ( count(d.dwl_id) = 0, 0, SUM(CAST(JSON_EXTRACT(d.dwl_details, "$.total.memberWinLoss") AS DECIMAL(65,10)))) totalWinLoss,
                    IF ( count(d.dwl_id) = 0, 0, SUM(CAST(JSON_EXTRACT(d.dwl_details, "$.total.grossCommission") AS DECIMAL(65,10)))) totalGrossComm,
                    IF ( count(d.dwl_id) = 0, 0, SUM(CAST(JSON_EXTRACT(d.dwl_details, "$.total.memberCommission") AS DECIMAL(65,10)))) totalCommission,
                    d.dwl_product_id,
                    d.dwl_currency_id
                FROM dwl d
		WHERE
                    d.dwl_currency_id = :currency AND
                    d.dwl_date >= :fromDWL AND d.dwl_date <= :toDWL AND
                    d.dwl_status = :dwlCompleted'
                    . (!empty($products) ? ' AND d.dwl_product_id IN (:products)': '')
                    . ' GROUP BY d.dwl_product_id
            ) dwlf ON dwlf.dwl_product_id = product.product_id'
            . ' INNER JOIN ('
                . 'SELECT p.product_id, COUNT(IF(IFNULL(st.total_turnover, 0) = 0, NULL, 1)) num_active_accounts FROM product p LEFT JOIN ('
                . 'SELECT st.subtransaction_customer_product_id, dwl.dwl_product_id product_id,SUM(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.turnover") AS DECIMAL(65, 10))) total_turnover'
                . ' FROM sub_transaction st INNER JOIN dwl'
                . ' ON st.subtransaction_dwl_id = dwl.dwl_id AND dwl.dwl_currency_id = :currency AND dwl.dwl_status = :dwlCompleted'
                . ' AND dwl.dwl_date >= :fromDWL AND dwl.dwl_date <= :toDWL'
                . (!empty($products) ? ' AND dwl.dwl_product_id IN (:products)' : '')
                . ' GROUP BY st.subtransaction_customer_product_id) st ON p.product_id = st.product_id'
                . (!empty($products) ? ' WHERE p.product_id IN (:products)': '')
                . ' GROUP BY p.product_id'
            . ') sts ON product.product_id = sts.product_id'
            . ' INNER JOIN (SELECT product_id, COUNT(cproduct_id) total_product'
                . ' FROM product LEFT JOIN ('
                . 'customer_product INNER JOIN customer ON customer_id = cproduct_customer_id AND customer_currency_id = :currency'
                . (!empty($products) ? ' AND cproduct_product_id IN (:products)' : '')
            . ') ON product_id = cproduct_product_id'
            . (!empty($products) ? ' WHERE product_id IN (:products)' : '')
            . ' GROUP BY product_id) ct ON ct.product_id = product.product_id'
            , ''
        );

        $queryBuilder->setParameter('fromDWL', $from);
        $queryBuilder->setParameter('toDWL', $to);
        $queryBuilder->groupBy('product.product_id');
        $queryBuilder->setParameter('deposit', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_DEPOSIT);
        $queryBuilder->setParameter('dwl', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_DWL);
        $queryBuilder->setParameter('dwlCompleted', \DbBundle\Entity\DWL::DWL_STATUS_COMPLETED);
        $queryBuilder->setParameter('currency', $currency);
        $queryBuilder->setParameter('userIds', $apiUserIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    public function getCustomerProductDWL(int $customerProductId, array $filters = [], int $limit = 20, int $offset = 0)
    {
        $queryBuilder = $this->getCustomerProductDWLQuery($customerProductId, $filters);
        $queryBuilder->select('st.*', 'd.*');
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        $result = array_map(
            function ($item) {
                $item['subtransaction_details'] = json_decode($item['subtransaction_details'], true);

                return $item;
            },
            $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC)
        );

        return $result;
    }

    public function getCustomerProductDWLTotal(int $customerProductId, array $filters = [])
    {
        $queryBuilder = $this->getCustomerProductDWLQuery($customerProductId, $filters);
        $queryBuilder->select('COUNT(*) total');

        return $queryBuilder->execute()->fetchColumn();
    }

    private function getCustomerProductDWLQuery(int $customerProductId, array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder();

        $joinCondition = $queryBuilder->expr()->andX();
        $joinCondition->add('d.dwl_id = st.subtransaction_dwl_id');

        if (array_get($filters, 'from')) {
            $joinCondition->add('d.dwl_date >= :from');
            $queryBuilder->setParameter('from', $filters['from']);
        }

        if (array_get($filters, 'to')) {
            $joinCondition->add('d.dwl_date <= :to');
            $queryBuilder->setParameter('to', $filters['to']);
        }

        $queryBuilder
            ->from('sub_transaction', 'st')
            ->innerJoin('st', 'dwl', 'd', $joinCondition)
            ->where('st.subtransaction_customer_product_id = :customerProductId')
            ->andWhere('st.subtransaction_type = :dwl')
            ->orderBy('d.dwl_date', 'DESC')
            ->setParameter('dwl', \DbBundle\Entity\Transaction::TRANSACTION_TYPE_DWL)
            ->setParameter('customerProductId', $customerProductId)
        ;

        return $queryBuilder;
    }

    private function getApiUserIds()
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('user_id');
        $queryBuilder->from('user');
        $queryBuilder->where('user_username LIKE :username');
        $queryBuilder->setParameter('username', '%_api');

        $stmt = $queryBuilder->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->doctrine->getConnection()->createQueryBuilder();
    }
}
