<?php

namespace ReportBundle\Manager;

use AppBundle\Manager\AbstractManager;

use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\CustomerProduct;
use Doctrine\DBAL\Connection;
use MemberBundle\Manager\InactiveMemberManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Report;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use DbBundle\Entity\Transaction;
use \Doctrine\ORM\AbstractQuery;


class ReportCustomerManager extends AbstractManager
{
    private $inactiveMemberManager;

    public function __construct(InactiveMemberManager $inactiveMemberManager)
    {
        $this->inactiveMemberManager = $inactiveMemberManager;
    }

    public function getMemberDwlReports(
        Currency $currency,
        $reportStartDate,
        $reportEndDate,
        ?string $customerNameQueryString = null,
        ?int $memberId = null,
        bool $hideZeroValueRecords = false,
        ?int $limit = null,
        ?int $page = null
    ) {

        $offset = ($page - 1) * $limit;
        $query = $this->getDwlReportQuery(
            $currency,
            $reportStartDate,
            $reportEndDate,
            $customerNameQueryString,
            $memberId,
            false,
            $hideZeroValueRecords,
            $limit ,
            $offset);
        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        $result = [];
        foreach ($iterableResult as $key => $row) {
            $memberDwlReport = array_pop($row);
            $result[$key]['currency_code'] = $memberDwlReport['currencyCode'];
            $result[$key]['customer_available_balance_by_end_of_report_dates'] = $memberDwlReport['balanceAsOf'];
            $result[$key]['customer_current_balance'] = $memberDwlReport['totalCustomerProductBalance'];
            $result[$key]['customer_full_name'] = $memberDwlReport['customerName'];
            $result[$key]['customer_id'] = $memberDwlReport['customerId'];
            $result[$key]['dwl_amount'] = $memberDwlReport['total'];
            $result[$key]['dwl_commission'] = $memberDwlReport['commission'];
            $result[$key]['dwl_gross'] = $memberDwlReport['gross'];
            $result[$key]['dwl_turnover'] = $memberDwlReport['turnover'];
            $result[$key]['dwl_win_loss'] = $memberDwlReport['win_loss'];
        }

        return $result;
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
        $query = $this->getDwlReportQuery($currency, $reportStartDate, $reportEndDate, $customerNameQueryString);
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

        $filteredCustomerProducts = $this->getRepository()->getCustomerProductReport(
            $filters,
            $limit = 0,
            $offset,
            ['cp.cproduct_id' => 'ASC'],
            ['cp.cproduct_id'],
            ['c.customer_id', 'cp.cproduct_id', 'cp.cproduct_username', 'cp.cproduct_product_id product_id', 'c.customer_currency_id currency_id', 'cu.currency_code', 'cp.cproduct_balance current_balance', 'p.product_deleted_at']
        );

        if (count($report) > 0){
            $customerProductIds = array_map(
                function ($customerProduct) {
                    return $customerProduct['cproduct_id'];
                },
                array_get($filters, 'isForSkypeBetting', true) ? $filteredCustomerProducts : $report
            );

            $beforeDate = \DateTime::createFromFormat('Y-m-d', $filters['from'])->modify('-1 day')->format('Y-m-d');
            $afterDate = \DateTime::createFromFormat('Y-m-d', $filters['to'])->modify('+1 day')->format('Y-m-d');
            $reportEndDate = \DateTime::createFromFormat('Y-m-d', $filters['to']);

            $beforeTheFromDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], null, $beforeDate, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
            $afterTheToDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $afterDate, null, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
            $rangeDateReport = $this->reportModifyKeys($this->getRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $filters['from'], $filters['to'], ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));

            if (array_get($filters, 'isForSkypeBetting', true) && array_get($filters, 'hideZeroValueRecords', true)) {
                $skypeBettingData = $filteredCustomerProducts;
                $reportDataAdded = $this->addReportData($skypeBettingData, $beforeTheFromDateReport, $afterTheToDateReport, $rangeDateReport, $reportEndDate);
                $reportAlteredFromSkypeBetting = $this->alterReportsRelatedForSkypeBetting($reportDataAdded, $reportEndDate);
                $report = array_slice($reportAlteredFromSkypeBetting, $offset, array_get($filters, 'limit', 10));
            } else {
                $report = $this->addReportData($report, $beforeTheFromDateReport, $afterTheToDateReport, $rangeDateReport, $reportEndDate);
            }
        }

        $currencies = $filters['currencies'] ?? [];
        if (array_has($filters, 'currency')) {
            $currencies[] = $filters['currency'];
        }

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
            'recordsFiltered' => (array_get($filters, 'isForSkypeBetting', true) && array_get($filters, 'hideZeroValueRecords', true)) ? count($reportAlteredFromSkypeBetting) : count($filteredCustomerProducts),
            'recordsTotal' => (int) $totalCustomerProducts,
            'limit' => $limit,
            'page' => $page,
        ];

        return $report;
    }

    private function addReportData(array $report, array $beforeTheFromDateReport, array $afterTheToDateReport, array $rangeDateReport, \DateTimeInterface $reportEndDate): array
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
                $customerProduct['end_of_report_date_balance'] = $customerProduct['current_balance'] - $customerProduct['after']['total'];

                return $customerProduct;
            },
            $report
        );
        return $report;
    }

    private function alterReportsRelatedForSkypeBetting(array $report, \DateTimeInterface $reportEndDate): array
    {
        $skypeBettingData = $this->getSkypeBettingCustomerBalances($reportEndDate);

        foreach ($report as $key => $memberReport) {
            $memberProductId = $report[$key]['after']['cproduct_id'] ?? null;
            if ($this->isSkypeBettingProduct($memberProductId)) {
                $customerIdAtBetadmin = $report[$key]['customerIdAtBetAdmin'];
                $report[$key]['current_balance'] = $skypeBettingData[$customerIdAtBetadmin]['current_balance'] ?? 0;
                $report[$key]['end_of_report_date_balance'] = $skypeBettingData[$customerIdAtBetadmin]['end_of_day_balance'] ?? 0;
            }
            if ($report[$key]['current_balance'] < 1 AND $report[$key]['dwl_turnover'] == 0){
                unset($report[$key]);
            }
            unset($report[$key]['customerIdAtBetAdmin']);
        }

        return $report;
    }

    private function getSkypeBettingCustomerBalances(\DateTimeInterface $reportEndDate)
    {
        return $this->container->get('brokerage.brokerage_service')->getMembersComponent()->getAllMembers($reportEndDate);
    }

    private function isSkypeBettingProduct(int $memberProductId): bool
    {
        $memberProduct = $this->getCustomerProductRepository()->find($memberProductId);
        if (!$memberProduct instanceof CustomerProduct) {
            return  false;
        }

        return $memberProduct->isSkypeBetting();
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

    public function printMemberProductsCsvReport(Product $product, Currency $currencyEntity, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $memberProductUsernameQueryString = null, bool $reportIsZeroValue = false)
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getMemberProductReportQuery($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString, $reportIsZeroValue);

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

        if ($product->isSkypeBetting()) {
            $skypeBettingData = $this->getSkypeBettingCustomerBalances($reportEndDate);
        }

        foreach ($iterableResult as $row) {
            $member = array_pop($row);

            if ($product->isSkypeBetting() && $reportIsZeroValue) {
                $customerIdAtBetadmin = $member['customerIdAtBetadmin'];

                if (($skypeBettingData[$customerIdAtBetadmin]['current_balance'] ?? 0) < 1 AND ($member['turnover'] ?? 0) == 0) {
                   unset($member);
                } else {
                    echo '"'.$member['memberProductName'] . '",';
                    echo ($member['turnover'] ?? 0 ). ',';
                    echo ($member['gross'] ?? 0) . ',';
                    echo ($member['win_loss'] ?? 0) . ',';
                    echo ($member['commission'] ?? 0) . ',';

                    echo ($skypeBettingData[$customerIdAtBetadmin]['end_of_day_balance'] ?? 0) . ',';
                    echo ($skypeBettingData[$customerIdAtBetadmin]['current_balance'] ?? 0) . ',';

                    echo $member['isNotYetDeletedWithinReportDateRange'] ;

                    $turnOverTotal += $member['turnover'];
                    $grossCommissionTotal += $member['gross'];
                    $winlossTotal += $member['win_loss'];
                    $commissionTotal += $member['commission'];
                    $availableBalaneAsOfReportEndDateTotal += $member['balanceAsOf'];
                    $currentBalanceTotal += $member['totalCustomerProductBalance'];

                    echo "\n";
                }

            } else {
                echo '"'.$member['memberProductName'] . '",';
                echo ($member['turnover'] ?? 0 ). ',';
                echo ($member['gross'] ?? 0) . ',';
                echo ($member['win_loss'] ?? 0) . ',';
                echo ($member['commission'] ?? 0) . ',';

                if ($product->isSkypeBetting()) {
                    $customerIdAtBetadmin = $member['customerIdAtBetadmin'];
                    echo ($skypeBettingData[$customerIdAtBetadmin]['end_of_day_balance'] ?? 0) . ',';
                    echo ($skypeBettingData[$customerIdAtBetadmin]['current_balance'] ?? 0) . ',';
                } else {
                    echo ($member['balanceAsOf'] ?? 0) . ',';
                    echo ($member['totalCustomerProductBalance'] ?? 0) . ',';
                }

                echo $member['isNotYetDeletedWithinReportDateRange'] ;

                $turnOverTotal += $member['turnover'];
                $grossCommissionTotal += $member['gross'];
                $winlossTotal += $member['win_loss'];
                $commissionTotal += $member['commission'];
                $availableBalaneAsOfReportEndDateTotal += $member['balanceAsOf'];
                $currentBalanceTotal += $member['totalCustomerProductBalance'];

                echo "\n";
            }
        }

        echo "Total,";
        echo $turnOverTotal .',';
        echo $grossCommissionTotal .',';
        echo $winlossTotal .',';
        echo $commissionTotal .',';
        echo $availableBalaneAsOfReportEndDateTotal .',';
        echo $currentBalanceTotal ."\n";
    }

    public function printMemberProductsByMemberCsvReport(Customer $member, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $memberProductUsernameQueryString = null)
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();
        $query = $this->getMemberProductByMemberReportQuery($member, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);

        // filter details
        echo "From: ". $reportStartDate->format('Y-m-d') ."\n";
        echo "To: ". $reportEndDate->format('Y-m-d') ."\n";

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

    public function getMemberProductsReportSummary(?int $productId, int $currencyId, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, ?String $memberProductUsernameQueryString = null, bool $reportIsZeroValue = false): array
    {
        $currency = $this->getEntityManager()->getRepository(Currency::class)->findOneById($currencyId);
        $product = null;
        if ($productId !== null ) {
            $product = $this->getEntityManager()->getRepository(Product::class)->findOneById($productId);
        }
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();

        $query = $this->getMemberProductReportQuery($product, $currency, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString, $reportIsZeroValue);

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

    public function printMemberDwlReports(
        Currency $currencyEntity,
        \DateTimeInterface $reportStartDate,
        \DateTimeInterface $reportEndDate,
        ?string $customerNameQueryString = null,
        ?int $memberId = null,
        bool $hideInactiveMembers = false,
        bool $hideZeroValueRecords = false
    ) {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();

        $query = $this->getDwlReportQuery(
            $currencyEntity,
            $reportStartDate,
            $reportEndDate,
            $customerNameQueryString,
            $memberId,
            $hideInactiveMembers,
            $hideZeroValueRecords
        );

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

    private function getMemberProductReportQuery(?Product $product, Currency $currency, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, $memberProductUsernameQueryString = null, bool $reportIsZeroValue = false): AbstractQuery
    {
        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('memberProductName','memberProductName');
        $resultsMap->addScalarResult('customerIdAtBetadmin','customerIdAtBetadmin');
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

        $productSearchCondition = '';
        if ($product instanceof  Product) {
            $productSearchCondition = ' AND cproduct_product_id = :productId';
        }

        $reportIsZeroValueCondition = '';
        if ($reportIsZeroValue && !$product->isSkypeBetting()) {
            $reportIsZeroValueCondition = ' AND (turnover <> 0 OR totalCustomerProductBalance >= 1)';
        }

        $sql = '
            SELECT cproduct_username as memberProductName,
              cproduct_bet_sync_id as customerIdAtBetadmin,
              turnover,
              gross,
              win_loss,
              commission,
              total,
              transactionAmountFromEndOfReportDateToPresentDate,
              totalCustomerProductBalance,
              (totalCustomerProductBalance - IFNULL(transactionAmountFromEndOfReportDateToPresentDate,0)) as balanceAsOf,
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
            WHERE customer_currency_id = :currencyId
            '. $productSearchCondition .'
            '. $memberProductUsernameSearchCondition .'
            '. $reportIsZeroValueCondition .'
            ORDER BY cproduct_id ASC
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
        if ($product instanceof  Product) {
            $query->setParameter('productId', $product->getId());
        }
        if ($memberProductUsernameQueryString !== null ){
            $query->setParameter('memberProductUsernameQueryString', '%'.$memberProductUsernameQueryString.'%');
        }

        return $query;
    }

    private function getMemberProductByMemberReportQuery(Customer $member, \DateTimeInterface $reportStartDate, \DateTimeInterface $reportEndDate, $memberProductUsernameQueryString = null): AbstractQuery
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
              (totalCustomerProductBalance - IFNULL(transactionAmountFromEndOfReportDateToPresentDate,0)) as balanceAsOf,
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
            WHERE cproduct_customer_id = :memberId
            '. $memberProductUsernameSearchCondition .'
            ORDER BY cproduct_id ASC
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
        $query->setParameter('memberId', $member->getId());


        if ($memberProductUsernameQueryString !== null ){
            $query->setParameter('memberProductUsernameQueryString', '%'.$memberProductUsernameQueryString.'%');
        }

        return $query;
    }

    private function getDwlReportQuery(
        Currency $currency,
        \DateTimeInterface $reportStartDate,
        \DateTimeInterface $reportEndDate,
        ?string $customerNameQueryString = null,
        ?int $memberId = null,
        bool $hideInactiveMembers = false,
        bool $hideZeroValueMembers = false,
        ?int $limit = null,
        ?int $offset = null
    ) {

        $resultsMap = new ResultSetMapping();
        $resultsMap->addScalarResult('customer_id','customerId');
        $resultsMap->addScalarResult('customerName','customerName');
        $resultsMap->addScalarResult('currencyCode','currencyCode');
        $resultsMap->addScalarResult('turnover','turnover');
        $resultsMap->addScalarResult('gross','gross');
        $resultsMap->addScalarResult('win_loss','win_loss');
        $resultsMap->addScalarResult('commission','commission');
        $resultsMap->addScalarResult('total','total');
        $resultsMap->addScalarResult('balanceAsOf','balanceAsOf');
        $resultsMap->addScalarResult('totalCustomerProductBalance','totalCustomerProductBalance');

        $customerSearchCondition = '';
        if ($customerNameQueryString !== null && $customerNameQueryString !== ''){
            $customerSearchCondition = ' AND (customer_full_name LIKE :customerNameQueryString OR customer_fname LIKE :customerNameQueryString OR customer_lname LIKE :customerNameQueryString)';
        }

        $excludeInactiveMemberCondition = '';
        if ($hideInactiveMembers === true) {
            $excludeInactiveMemberCondition = ' AND (customer_id NOT IN (SELECT inactive_member_id from inactive_member) )';
        }

        $limitClause = '';
        $offsetClause = '';
        $zeroValueMembersClause = '';
        if (!empty($limit)) {
            $limitClause = 'LIMIT '. (int) $limit .' ';
        }
        if (!empty($offset)) {
            $offsetClause = 'OFFSET '. (int) $offset .' ';
        }

        if ($hideZeroValueMembers === true) {
            $zeroValueMembersClause = 'AND NOT (turnover = 0 AND  (IFNULL((IFNULL(customerBalance.totalCustomerProductBalance,0) - IFNULL(transactionsAfterReportDates.transactionAmount,0)),0)) = 0 )';
        }

        $memberIdSearchClause = '';
        if ($memberId !== null) {
            $memberIdSearchClause = ' AND customer_id = :memberId';
        }

        // create temporary table containing balance,end_of_day balance
        $createBetDataTable = 'CREATE TEMPORARY TABLE IF NOT EXISTS betadmin_data (
              customer_id_at_bet_admin int PRIMARY KEY,
              end_of_day_balance float,
              current_balance float
            )';
        $truncateBetDataTable = 'TRUNCATE TABLE betadmin_data';
        $em = $this->getDoctrine()->getManager();
        $betTableStatement = $em->getConnection()->prepare($createBetDataTable);
        $betTableStatement->execute();
        $truncateBetTableStatement = $em->getConnection()->prepare($truncateBetDataTable);
        $truncateBetTableStatement->execute();

        $skypeBettingData = $this->getSkypeBettingCustomerBalances($reportEndDate);
        foreach ($skypeBettingData as $customerIdAtBetAdmin => $betData) {
            $insertBetDataQuery = sprintf('INSERT into betadmin_data SET customer_id_at_bet_admin=%s, end_of_day_balance=%s, current_balance=%s', $customerIdAtBetAdmin,( $betData['end_of_day_balance'] ?? 0), ($betData['current_balance'] ?? 0));
            $insertBetDataStatement = $em->getConnection()->prepare($insertBetDataQuery);
            $insertBetDataStatement->execute();
        }

        // transactionsAfterReportDates subquery does not include betadmin products
        // customerBalance subquery does not include betadmin producs
        $sql = 'SELECT
                    customer_id,
                    customer_full_name as customerName,
                    currency_code as currencyCode,
                    (IFNULL(customerBalance.totalCustomerProductBalance,0) + IFNULL(customerBetadminBalance.totalBetAdminCustomerProductBalance, 0)) as totalCustomerProductBalance,
                    (IFNULL(( IFNULL(customerBalance.totalCustomerProductBalance,0) - IFNULL(transactionsAfterReportDates.transactionAmount,0)),0) + IFNULL(customerBetadminBalance.totalBetAdminEndOfDayBalance,0)) as balanceAsOf,
                    IFNULL(dwl.turnover, 0) as turnover,
                    IFNULL(dwl.gross, 0) as gross,
                    IFNULL(dwl.win_loss, 0) as win_loss,
                    IFNULL(dwl.commission, 0) as commission,
                    IFNULL(dwl.total, 0) as total
                FROM customer
                LEFT JOIN currency on currency_id = customer.customer_currency_id
                LEFT JOIN (SELECT
                             cproduct_customer_id  AS customerId,
                             SUM(cproduct_balance) AS totalCustomerProductBalance
                           FROM customer_product
                           WHERE cproduct_bet_sync_id is null
                           GROUP BY cproduct_customer_id) customerBalance ON customerBalance.customerId = customer_id
                LEFT JOIN (SELECT
                             cproduct_customer_id  AS customerId,
                             SUM(betadmin_data.current_balance) AS totalBetAdminCustomerProductBalance,
                             SUM(betadmin_data.end_of_day_balance) AS totalBetAdminEndOfDayBalance
                           FROM customer_product
                           JOIN betadmin_data ON betadmin_data.customer_id_at_bet_admin = cproduct_bet_sync_id
                           WHERE cproduct_bet_sync_id is NOT NULL
                           GROUP BY cproduct_customer_id
                ) customerBetadminBalance ON customerBetadminBalance.customerId = customer_id
                LEFT JOIN (SELECT
                             cproduct_customer_id AS customerId,
                             IFNULL(SUM(IF(subtransaction_type IN (:subtransactionTypeDeposit, :subtransactionTypeDwl),
                                           IFNULL(subtransaction_amount, 0), 0)), 0)
                             - IFNULL(SUM(IF(subtransaction_type = :subtransactionTypeWithdrawal OR
                                             (subtransaction_type = :subtransactionTypeBet AND
                                              JSON_CONTAINS(subtransaction_details, \'{"betSettled": false}\') = 1),
                                             IFNULL(subtransaction_amount, 0), 0)),
                                      0)          AS transactionAmount
                           FROM sub_transaction
                             LEFT JOIN transaction t ON sub_transaction.subtransaction_transaction_id = t.transaction_id
                             LEFT JOIN customer_product ON cproduct_id = subtransaction_customer_product_id
                           WHERE transaction_date >= DATE(:transactionsAfterThisReportRangeStartDate)
                                 AND cproduct_bet_sync_id is NULL
                                 AND transaction_is_voided = :transactionNotVoidedFlag
                                 AND transaction_status = :transactionCompletedFlag
                           GROUP BY cproduct_customer_id
                          ) transactionsAfterReportDates ON transactionsAfterReportDates.customerId = customer_id
                LEFT JOIN (select
                         transaction_customer_id,
                         SUM(IFNULL(CAST(JSON_EXTRACT(subtransaction_details, "$.dwl.turnover") AS DECIMAL(65, 10)),0)) turnover,
                                                  SUM(IFNULL(CAST(JSON_EXTRACT(subtransaction_details, "$.dwl.gross") AS DECIMAL(65, 10)), 0)) gross,
                                                  SUM(IFNULL(CAST(JSON_EXTRACT(subtransaction_details, "$.dwl.winLoss") AS DECIMAL(65, 10)),0)) win_loss,
                                                  SUM(IFNULL(CAST(JSON_EXTRACT(subtransaction_details, "$.dwl.commission") AS DECIMAL(65, 10)),0)) commission,
                                                  SUM(IFNULL(CAST(IF(dwl.dwl_id IS NOT NULL, subtransaction_amount, 0) AS DECIMAL(65, 10)), 0)) AS total
                       FROM transaction
                         JOIN dwl on dwl.dwl_id = transaction.dwl_id
                         LEFT JOIN sub_transaction ON transaction.transaction_id = sub_transaction.subtransaction_transaction_id

                       WHERE dwl.dwl_status = :dwlStatusTypeFlag AND dwl.dwl_date BETWEEN DATE(:reportStartDate) AND DATE(:reportEndDate)
                             AND transaction_currency_id = :currencyId
                             AND transaction_type = :dwlStatusTypeFlag
                       GROUP BY transaction_customer_id) dwl ON customer_id = dwl.transaction_customer_id
                WHERE customer_currency_id = :currencyId
                 ' . $memberIdSearchClause .'
                 ' . $zeroValueMembersClause .'
                 ' . $customerSearchCondition .'
                 ' . $excludeInactiveMemberCondition .'
                ORDER BY customer_id
                ' . $limitClause . '
                ' . $offsetClause . '
        ';

        //AND turnover > 0 AND  (customerBalance.totalCustomerProductBalance - transactionsAfterReportDates.transactionAmount) > 0



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
        if (!empty($memberIdsToExclude)) {
            $query->setParameter('memberIdsToExclude', $memberIdsToExclude, Connection::PARAM_INT_ARRAY);
        }
        if ($memberId !== null) {
            $query->setParameter('memberId', $memberId);
        }

        return $query;
    }
}
