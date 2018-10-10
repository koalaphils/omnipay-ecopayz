<?php

namespace ReportBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use DbBundle\Entity\Transaction;
use \Doctrine\ORM\AbstractQuery;

class ReportCustomerManager extends AbstractManager
{
    public function getReportCustomerList(array $filters = [], int $limit = 20, int $page = 1)
    {
        $offset = ($page - 1) * $limit;
        $customers = $this->getRepository()->getCustomerWithBalance($filters, $limit, $offset);
        $modifiedKeyCustomers = [];
        $customersProductIds = [];
        foreach ($customers as $customer) {
            $customerProductIds = explode(',', $customer['customer_product_ids']);
            $customersProductIds = array_merge($customersProductIds, $customerProductIds);
            $modifiedKeyCustomers[$customer['customer_id']] = $customer;
            $modifiedKeyCustomers[$customer['customer_id']]['dwl'] = [
                'turnover' => 0,
                'win_loss' => 0,
                'gross' => 0,
                'commission' => 0,
                'amount' => 0,
                'currency_code' => $customer['currency_id'],
                'customer_id' => $customer['customer_id'],
            ];
            $modifiedKeyCustomers[$customer['customer_id']]['after'] = [
                'deposit' => 0,
                'withdraw' => 0,
                'winloss' => 0,
                'total' => 0,
                'customer_id' => $customer['customer_id'],
            ];
        }
        $customers = $modifiedKeyCustomers;
        unset($modifiedKeyCustomers);

        $filters['customerProductIds'] = $customersProductIds;
        $selects = ['customer_id', 'currency_code'];
        $customersDWLSummary = $this->getRepository()->getCustomerProductReport($filters, 0, 0, ['c.customer_id' => 'ASC'], ['c.customer_id'], $selects);
        foreach ($customersDWLSummary as $customerDWLSummary) {
            $customers[$customerDWLSummary['customer_id']]['dwl'] = $customerDWLSummary;
        }
        $afterDate = \DateTime::createFromFormat('Y-m-d', $filters['to'])->modify('+1 day')->format('Y-m-d');
        $afterTheToDateReport = $this->getRepository()->computeCustomerProductsTotalTransactions(
            $filters,
            $afterDate,
            null,
            ['c.customer_id'],
            ['c.customer_id' => 'ASC'],
            ['customer_id']
        );
        foreach ($afterTheToDateReport as $record) {
            $customers[$record['customer_id']]['after'] = $record;
        }

        $totalFilteredCustomer = $this->getRepository()->countCustomers($filters);
        unset($filters['customer_search']);
        $totalCustomer = $this->getRepository()->countCustomers($filters);

        $report = [
            'records' => array_values($customers),
            'recordsFiltered' => $totalFilteredCustomer,
            'recordsTotal' => $totalCustomer,
            'limit' => $limit,
            'page' => $page,
        ];

        return $report;
    }

    /**
     * @return array [
            "dwl" => [
                "turnover" => float
                "winLoss" => float
                "gross" => float
                "commission" => float
                "amount" => float
            ],
            "customer" => [
                "totalCustomerProduct" => float
                "totalCustomer" => float
                "totalBalances" => float
                "totalBalanceUpToEndOfReportDateRange" => float
            ]
    ]
     */
    public function getReportCustomerTotal(Currency $currency, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $customerNameQueryString = null)
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getReportQuery($currency, $reportStartDate, $reportEndDate, $customerNameQueryString);
        $turnOverTotal = 0;
        $grossCommissionTotal = 0;
        $winlossTotal = 0;
        $commissionTotal = 0;
        $totalTransactionsTotal = 0;
        $availableBalanceAsOfReportEndDateTotal = 0;
        $currentBalanceTotal = 0;

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        foreach ($iterableResult as $row) {
            $member = array_pop($row);
            $turnOverTotal += $member['turnover'];
            $grossCommissionTotal += $member['gross'];
            $winlossTotal += $member['win_loss'];
            $commissionTotal += $member['commission'];
            $totalTransactionsTotal += $member['total'];
            $availableBalanceAsOfReportEndDateTotal += $member['balanceAsOf'];
            $currentBalanceTotal += $member['totalCustomerProductBalance'];
        }

        $defaultDisplayValue = '0.00';

        $total = [
            'dwl' => [
                'turnover' => $turnOverTotal,
                'winLoss' => $winlossTotal,
                'gross' => $grossCommissionTotal,
                'commission' => $commissionTotal,
                'amount' => $totalTransactionsTotal,
            ],
            'customer' => [
                'totalCustomerProduct' => $defaultDisplayValue,
                'totalCustomer' => $defaultDisplayValue,
                'totalBalances' => $currentBalanceTotal,
                'totalBalanceUpToEndOfReportDateRange' => $availableBalanceAsOfReportEndDateTotal,
            ]
        ];

        return $total;
    }

    public function getCustomerProductList(array $filters = [], int $limit = 20, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;
        $report = $this->getRepository()->getCustomerProductReport(
            $filters,
            $limit,
            $offset,
            ['cp.cproduct_id' => 'ASC'],
            ['cp.cproduct_id'],
            ['c.customer_id', 'cp.cproduct_id', 'cp.cproduct_username', 'cp.cproduct_product_id product_id', 'c.customer_currency_id currency_id', 'cu.currency_code', 'cp.cproduct_balance current_balance', 'p.product_deleted_at']
        );

        $customerProductIds = array_map(
            function ($customerProduct) {
                return $customerProduct['cproduct_id'];
            },
            $report
        );

        $beforeDate = \DateTime::createFromFormat('Y-m-d', $filters['from'])->modify('-1 day')->format('Y-m-d');
        $afterDate = \DateTime::createFromFormat('Y-m-d', $filters['to'])->modify('+1 day')->format('Y-m-d');

        $beforeTheFromDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], null, $beforeDate, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
        $afterTheToDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $afterDate, null, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
        $rangeDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $filters['from'], $filters['to'], ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));


        $report = $this->addReportData($report, $beforeTheFromDateReport, $afterTheToDateReport, $rangeDateReport);

        $currencies = $filters['currencies'] ?? [];
        if (array_has($filters, 'currency')) {
            $currencies[] = $filters['currency'];
        }

        $totalFilteredCustomerProducts = $this->getCustomerProductRepository()->getCustomerProductListFilterCount([
            'customerID' => $filters['customer'] ?? [],
            'products' => $filters['products'] ?? [],
            'currencies' => $currencies,
            'search' => $filters['search'] ?? '',
        ]);
        $totalCustomerProducts = $this->getCustomerProductRepository()->getCustomerProductListFilterCount([
            'customerID' => $filters['customer'] ?? [],
            'products' => $filters['products'] ?? [],
            'currencies' => $currencies,
        ]);

        $report = [
            'records' => array_map(
                function ($record) use ($filters) {
                    $record['link'] = $this->getRouter()->generate(
                        'report.product_customerproduct_dwl',
                        [
                            'currencyCode' => $record['currency_code'],
                            'productId' => $record['product_id'],
                            'customerProductId' => $record['cproduct_id'],
                            'filters' => ['from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? ''],
                        ]
                    );

                    return $record;
                },
                $report
            ),
            'recordsFiltered' => (int) $totalFilteredCustomerProducts,
            'recordsTotal' => (int) $totalCustomerProducts,
            'limit' => $limit,
            'page' => $page,
        ];

        return $report;
    }

    private function addReportData(array $report, array $beforeTheFromDateReport, array $afterTheToDateReport, array $rangeDateReport): array
    {
        $report = array_map(
            function ($customerProduct) use ($beforeTheFromDateReport, $afterTheToDateReport, $rangeDateReport) {
                $customerProduct['before'] = $beforeTheFromDateReport[$customerProduct['cproduct_id']] ?? [
                        'cproduct_id' => $customerProduct['cproduct_id'],
                        'deposit' => 0,
                        'withdraw' => 0,
                        'winloss' => 0,
                        'total' => 0,
                    ];
                $customerProduct['after'] = $afterTheToDateReport[$customerProduct['cproduct_id']] ?? [
                        'cproduct_id' => $customerProduct['cproduct_id'],
                        'deposit' => 0,
                        'withdraw' => 0,
                        'winloss' => 0,
                        'total' => 0,
                    ];
                $customerProduct['range'] = $rangeDateReport[$customerProduct['cproduct_id']] ?? [
                        'cproduct_id' => $customerProduct['cproduct_id'],
                        'deposit' => 0,
                        'withdraw' => 0,
                        'winloss' => 0,
                        'total' => 0,
                    ];

                return $customerProduct;
            },
            $report
        );

        return $report;
    }

    protected function getRepository(): \ReportBundle\Repository\ReportCustomerRepository
    {
        return $this->container->get('report_customer.repository');
    }

    private function reportModifyKeys($result): array
    {
        $modifiedKeyResult = [];
        foreach ($result as $item) {
            $modifiedKeyResult[$item['cproduct_id']] = $item;
        }
        unset($result);

        return $modifiedKeyResult;
    }

    private function getCustomerProductRepository(): \DbBundle\Repository\CustomerProductRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\CustomerProduct::class);
    }

    public function printMemberProductsCsvReport(Product $product, Currency $currencyEntity, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $memberProductUsernameQueryString = null)
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getMemberProductReportQuery($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);

        // filter details
        echo "From: ". $reportStartDate->format('Y-m-d') ."\n";
        echo "To: ". $reportEndDate->format('Y-m-d') ."\n";
        echo "Product: ". $product->getName() . "\n";
        echo "Currency: ". $currencyEntity->getCode() ."\n";

        if ($memberProductUsernameQueryString !== null && $memberProductUsernameQueryString !== '') {
            echo "Member Product Username Search: ". $memberProductUsernameQueryString ." \n";
        }
        echo "\n";

        // Headers
        echo 'Member Product';
        echo ',';
        echo 'Turnover';
        echo ',';
        echo 'Gross Commission';
        echo ',';
        echo 'Win/Loss';
        echo ',';
        echo 'Commission';
        echo ',';
        echo '"Available Balance: '. $reportEndDate->format('F d, Y').'"';
        echo ',';
        echo 'Current Balance';
        echo ',';
        echo 'Status';
        echo "\n";

        $turnOverTotal = 0;
        $grossCommissionTotal = 0;
        $winlossTotal = 0;
        $commissionTotal = 0;
        $availableBalaneAsOfReportEndDateTotal = 0;
        $currentBalanceTotal = 0;

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        foreach ($iterableResult as $row) {
            $member = array_pop($row);
            echo '"'.$member['memberProductName'] . '",';
            echo ($member['turnover'] ?? 0 ). ',';
            echo ($member['gross'] ?? 0) . ',';
            echo ($member['win_loss'] ?? 0) . ',';
            echo ($member['commission'] ?? 0) . ',';
            echo ($member['balanceAsOf'] ?? 0) . ',';
            echo ($member['totalCustomerProductBalance'] ?? 0) . ',';
            echo $member['isNotYetDeletedWithinReportDateRange'] ;

            $turnOverTotal += $member['turnover'];
            $grossCommissionTotal += $member['gross'];
            $winlossTotal += $member['win_loss'];
            $commissionTotal += $member['commission'];
            $availableBalaneAsOfReportEndDateTotal += $member['balanceAsOf'];
            $currentBalanceTotal += $member['totalCustomerProductBalance'];


            echo "\n";
        }

        echo "Total,";
        echo $turnOverTotal .',';
        echo $grossCommissionTotal .',';
        echo $winlossTotal .',';
        echo $commissionTotal .',';
        echo $availableBalaneAsOfReportEndDateTotal .',';
        echo $currentBalanceTotal ."\n";
    }

    public function getMemberProductsReportSummary(int $productId, int $currencyId, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $memberProductUsernameQueryString = null): array
    {
        $currency = $this->getEntityManager()->getRepository(Currency::class)->findOneById($currencyId);
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneById($productId);
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getMemberProductReportQuery($product, $currency, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);

        $turnOverTotal = 0;
        $grossCommissionTotal = 0;
        $winlossTotal = 0;
        $commissionTotal = 0;
        $availableBalaneAsOfReportEndDateTotal = 0;
        $currentBalanceTotal = 0;

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        foreach ($iterableResult as $row) {
            $member = array_pop($row);

            $turnOverTotal += $member['turnover'];
            $grossCommissionTotal += $member['gross'];
            $winlossTotal += $member['win_loss'];
            $commissionTotal += $member['commission'];
            $availableBalaneAsOfReportEndDateTotal += $member['balanceAsOf'];
            $currentBalanceTotal += $member['totalCustomerProductBalance'];
        }

        return compact(
            'turnOverTotal',
            'grossCommissionTotal',
            'winlossTotal',
            'commissionTotal',
            'availableBalaneAsOfReportEndDateTotal',
            'currentBalanceTotal'
        );
    }

    public function printCsvReport(Currency $currencyEntity, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, String $customerNameQueryString = null)
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getReportQuery($currencyEntity, $reportStartDate, $reportEndDate, $customerNameQueryString);

        // filter details
        echo "From: ". $reportStartDate->format('Y-m-d') ."\n";
        echo "To: ". $reportEndDate->format('Y-m-d') ."\n";
        echo "Currency: ". $currencyEntity->getCode() ."\n";
        if ($customerNameQueryString !== null && $customerNameQueryString !== '')
        {
            echo "Customer Name Search: ". $customerNameQueryString ." \n";
        }
        echo "\n";


        // Headers
        echo 'Member';
        echo ',';
        echo 'Turnover';
        echo ',';
        echo 'Gross Commission';
        echo ',';
        echo 'Win/Loss';
        echo ',';
        echo 'Commission';
        echo ',';
        echo 'Total';
        echo ',';
        echo '"Available Balance: '. $reportEndDate->format('F d, Y') .'"';
        echo ',';
        echo 'Current Balance';
        echo "\n";

        $turnOverTotal = 0;
        $grossCommissionTotal = 0;
        $winlossTotal = 0;
        $commissionTotal = 0;
        $totalTransactionsTotal = 0;
        $availableBalaneAsOfReportEndDateTotal = 0;
        $currentBalanceTotal = 0;

        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        foreach ($iterableResult as $row) {
            $member = array_pop($row);
            echo '"' . $member['customerName'] . '",';
            echo $member['turnover'] . ',';
            echo $member['gross'] . ',';
            echo $member['win_loss'] . ',';
            echo $member['commission'] . ',';
            echo $member['total'] . ',';
            echo $member['balanceAsOf'] . ',';
            echo $member['totalCustomerProductBalance'] . ',';


            $turnOverTotal += $member['turnover'];
            $grossCommissionTotal += $member['gross'];
            $winlossTotal += $member['win_loss'];
            $commissionTotal += $member['commission'];
            $totalTransactionsTotal += $member['total'];
            $availableBalaneAsOfReportEndDateTotal += $member['balanceAsOf'];
            $currentBalanceTotal += $member['totalCustomerProductBalance'];


            echo "\n";


        }

        echo "Total,";
        echo $turnOverTotal .',';
        echo $grossCommissionTotal .',';
        echo $winlossTotal .',';
        echo $commissionTotal .',';
        echo $totalTransactionsTotal . ',';
        echo $availableBalaneAsOfReportEndDateTotal .',';
        echo $currentBalanceTotal ."\n";
    }

    /**
     * prevents large sql results from being loaded in the memory and consuming all the server's memory
     * using this prevents other queries to be run while one unbuffered query is still active
     */
    private function doNotLoadSqlResultsInMemoryAllAtOnce(): void
    {
        $em = $this->getDoctrine()->getManager();
        $pdo = $em->getConnection()->getWrappedConnection();
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    private function getMemberProductReportQuery(Product $product, Currency $currency, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, $memberProductUsernameQueryString = null): AbstractQuery
    {
        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('memberProductName','memberProductName');
        $resultsMap->addScalarResult('turnover','turnover');
        $resultsMap->addScalarResult('gross','gross');
        $resultsMap->addScalarResult('win_loss','win_loss');
        $resultsMap->addScalarResult('commission','commission');
        $resultsMap->addScalarResult('total','total');
        $resultsMap->addScalarResult('balanceAsOf','balanceAsOf');
        $resultsMap->addScalarResult('totalCustomerProductBalance','totalCustomerProductBalance');
        $resultsMap->addScalarResult('isNotYetDeletedWithinReportDateRange','isNotYetDeletedWithinReportDateRange');

        $memberProductUsernameSearchCondition = '';
        if ($memberProductUsernameQueryString !== null && $memberProductUsernameQueryString !== ''){
            $memberProductUsernameSearchCondition = ' AND cproduct_username LIKE :memberProductUsernameQueryString';
        }



        $sql = '
            SELECT cproduct_username as memberProductName, 
              turnover,
              gross,
              win_loss,
              commission,
              total,
              transactionAmountFromEndOfReportDateToPresentDate,
              totalCustomerProductBalance, 
              (totalCustomerProductBalance - transactionAmountFromEndOfReportDateToPresentDate) as balanceAsOf,
              IF(p.product_deleted_at IS NULL OR p.product_deleted_at NOT BETWEEN :reportStartDate and :reportEndDate,"Enabled","Disabled") as isNotYetDeletedWithinReportDateRange
            FROM customer_product
              LEFT JOIN customer on customer_id = customer_product.cproduct_customer_id
              LEFT JOIN product p on cproduct_product_id = p.product_id
              LEFT JOIN (SELECT
                           cproduct_id  as customerProductId,
                           SUM(cproduct_balance) as totalCustomerProductBalance
                         FROM customer_product
                         GROUP BY cproduct_id) customerProductBalance ON customerProductBalance.customerProductId = cproduct_id
              LEFT JOIN (SELECT subtransaction_customer_product_id,
                           SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.turnover") AS DECIMAL(65, 10)), 0)) turnover,
                           SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.gross") AS DECIMAL(65, 10)), 0)) gross,
                           SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.winLoss") AS DECIMAL(65, 10)), 0)) win_loss,
                           SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.commission") AS DECIMAL(65, 10)), 0)) commission,
                           SUM(IFNULL(CAST(IF(dwl.dwl_id IS NOT NULL, st.subtransaction_amount, 0) AS DECIMAL(65, 10)), 0)) as  total
                         FROM sub_transaction st
                           LEFT JOIN dwl on dwl_id = subtransaction_dwl_id
                         WHERE dwl_id is not null
                               AND dwl.dwl_status = :dwlStatusTypeFlag AND dwl.dwl_date BETWEEN DATE(:reportStartDate) AND DATE(:reportEndDate)
                         GROUP BY subtransaction_customer_product_id) trans on trans.subtransaction_customer_product_id = cproduct_id
              LEFT JOIN (SELECT cproduct_id  as customerProductId,
                                IFNULL(SUM( IF( subtransaction_type in (:subtransactionTypeDeposit, :subtransactionTypeDwl), IFNULL(subtransaction_amount, 0), 0)),0)
                                - IFNULL(SUM( IF(subtransaction_type = :subtransactionTypeWithdrawal OR (subtransaction_type = :subtransactionTypeBet AND JSON_CONTAINS(subtransaction_details, \'{"betSettled": false}\') = 1),IFNULL(subtransaction_amount, 0),0)),0) as transactionAmountFromEndOfReportDateToPresentDate
                         from sub_transaction
                           LEFT JOIN transaction t on sub_transaction.subtransaction_transaction_id = t.transaction_id
                           left join customer_product on cproduct_id = subtransaction_customer_product_id
                         where  transaction_date >= DATE(:transactionsAfterThisReportRangeStartDate)
                                and transaction_is_voided = :transactionNotVoidedFlag
                                and transaction_status = :transactionCompletedFlag
                         GROUP BY cproduct_id
                        ) transactionAmountsAfterReportDateUpToPresentDate ON transactionAmountsAfterReportDateUpToPresentDate.customerProductId = cproduct_id
            WHERE cproduct_product_id = :productId
            AND customer_currency_id = :currencyId
            '. $memberProductUsernameSearchCondition .'
            ORDER BY cproduct_id ASC
            LIMIT 4000000
        ';

        $query = $this->getDoctrine()->getManager()->createNativeQuery($sql, $resultsMap);
        $query->setParameter('dwlStatusTypeFlag', Transaction::TRANSACTION_TYPE_DWL);
        $query->setParameter('transactionCompletedFlag', Transaction::TRANSACTION_STATUS_END);
        $query->setParameter('transactionNotVoidedFlag', 0);
        $query->setParameter('subtransactionTypeDeposit', Transaction::TRANSACTION_TYPE_DEPOSIT);
        $query->setParameter('subtransactionTypeWithdrawal', Transaction::TRANSACTION_TYPE_WITHDRAW);
        $query->setParameter('subtransactionTypeDwl', Transaction::TRANSACTION_TYPE_DWL);
        $query->setParameter('subtransactionTypeBet', Transaction::TRANSACTION_TYPE_BET);

        $nextDayAfterReportDateRange = $reportEndDate->modify('+1 day');
        $query->setParameter('reportStartDate', $reportStartDate);
        $query->setParameter('reportEndDate', $reportEndDate);
        $query->setParameter('transactionsAfterThisReportRangeStartDate', $nextDayAfterReportDateRange);
        $query->setParameter('currencyId', $currency->getId());
        $query->setParameter('productId', $product->getId());
        if ($memberProductUsernameQueryString !== null ){
            $query->setParameter('memberProductUsernameQueryString', '%'.$memberProductUsernameQueryString.'%');
        }

        return $query;
    }

    private function getReportQuery(Currency $currency, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, $customerNameQueryString = null): \Doctrine\ORM\AbstractQuery
    {
        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('customerName','customerName');
        $resultsMap->addScalarResult('turnover','turnover');
        $resultsMap->addScalarResult('gross','gross');
        $resultsMap->addScalarResult('win_loss','win_loss');
        $resultsMap->addScalarResult('commission','commission');
        $resultsMap->addScalarResult('total','total');
        $resultsMap->addScalarResult('balanceAsOf','balanceAsOf');
        $resultsMap->addScalarResult('totalCustomerProductBalance','totalCustomerProductBalance');

        $customerSearchCondition = '';
        if ($customerNameQueryString !== null && $customerNameQueryString !== ''){
            $customerSearchCondition = ' AND (c.customer_full_name LIKE :customerNameQueryString OR c.customer_fname LIKE :customerNameQueryString OR c.customer_lname LIKE :customerNameQueryString)';
        }


        $sql = 'SELECT
                      c.customer_full_name as  customerName,
                      SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.turnover") AS DECIMAL(65, 10)), 0)) turnover,
                      SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.gross") AS DECIMAL(65, 10)), 0)) gross,
                      SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.winLoss") AS DECIMAL(65, 10)), 0)) win_loss,
                      SUM(IFNULL(CAST(JSON_EXTRACT(st.subtransaction_details, "$.dwl.commission") AS DECIMAL(65, 10)), 0)) commission,
                      SUM(IFNULL(CAST(IF(dwl.dwl_id IS NOT NULL, st.subtransaction_amount, 0) AS DECIMAL(65, 10)), 0)) as  total,
                      IFNULL(AVG(transactionAmountFromEndOfReportDateToPresentDate),0) as totalTransactionAmountFromEndofReportToPresentDay,
                      IFNULL(totalCustomerProductBalance - IFNULL(AVG(transactionAmountFromEndOfReportDateToPresentDate),0),0) as balanceAsOf,
                      totalCustomerProductBalance
                    FROM customer c
                      LEFT JOIN customer_product cp on cp.cproduct_customer_id = c.customer_id
                      LEFT JOIN sub_transaction st on cp.cproduct_id = st.subtransaction_customer_product_id
                      LEFT JOIN dwl on dwl.dwl_id = st.subtransaction_dwl_id
                      LEFT JOIN (SELECT
                                   cproduct_customer_id  as customerId,
                                   SUM(cproduct_balance) as totalCustomerProductBalance
                                 FROM customer_product
                                 GROUP BY cproduct_customer_id) customerBalance ON customerBalance.customerId = c.customer_id
                      LEFT JOIN (SELECT cproduct_customer_id as customerId,
                                        IFNULL(SUM( IF( subtransaction_type in (:subtransactionTypeDeposit, :subtransactionTypeDwl), IFNULL(subtransaction_amount, 0), 0)),0)
                                        - IFNULL(SUM( IF(subtransaction_type = :subtransactionTypeWithdrawal OR (subtransaction_type = :subtransactionTypeBet AND JSON_CONTAINS(subtransaction_details, \'{"betSettled": false}\') = 1),IFNULL(subtransaction_amount, 0),0)),0) as transactionAmountFromEndOfReportDateToPresentDate
                                 from sub_transaction
                                   LEFT JOIN transaction t on sub_transaction.subtransaction_transaction_id = t.transaction_id
                                   left join customer_product on cproduct_id = subtransaction_customer_product_id
                                 where  transaction_date >= DATE(:transactionsAfterThisReportRangeStartDate)
                                 and transaction_is_voided = :transactionNotVoidedFlag
                                 and transaction_status = :transactionCompletedFlag
                                 GROUP BY cproduct_customer_id
                                ) transactionAmountsAfterReportDateUpToPresentDate ON transactionAmountsAfterReportDateUpToPresentDate.customerId = c.customer_id
                    WHERE (dwl.dwl_status IS NULL OR (dwl.dwl_status = :dwlStatusTypeFlag AND dwl.dwl_date BETWEEN DATE(:reportStartDate) AND DATE(:reportEndDate)))
                          AND c.customer_currency_id = :currencyId
                          '. $customerSearchCondition .'
                    GROUP BY c.customer_id
                    LIMIT 2500000
                    ';

        $query = $this->getDoctrine()->getManager()->createNativeQuery($sql, $resultsMap);
        $query->setParameter('dwlStatusTypeFlag', Transaction::TRANSACTION_TYPE_DWL);
        $query->setParameter('transactionCompletedFlag', Transaction::TRANSACTION_STATUS_END);
        $query->setParameter('transactionNotVoidedFlag', 0);
        $query->setParameter('subtransactionTypeDeposit', Transaction::TRANSACTION_TYPE_DEPOSIT);
        $query->setParameter('subtransactionTypeWithdrawal', Transaction::TRANSACTION_TYPE_WITHDRAW);
        $query->setParameter('subtransactionTypeDwl', Transaction::TRANSACTION_TYPE_DWL);
        $query->setParameter('subtransactionTypeBet', Transaction::TRANSACTION_TYPE_BET);

        $nextDayAfterReportDateRange = $reportEndDate->modify('+1 day');
        $query->setParameter('reportStartDate', $reportStartDate);
        $query->setParameter('reportEndDate', $reportEndDate);
        $query->setParameter('transactionsAfterThisReportRangeStartDate', $nextDayAfterReportDateRange);
        $query->setParameter('currencyId', $currency->getId());
        if ($customerNameQueryString !== null ){
            $query->setParameter('customerNameQueryString', $customerNameQueryString.'%');
        }

        return $query;
    }
}
