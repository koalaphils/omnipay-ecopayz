<?php

namespace ReportBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Currency;
use ReportBundle\Repository\ReportProductRepository;

class ReportProductManager extends AbstractManager
{
    public function getReportProductList($filters = []): array
    {
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $filters['currency']]);
        $filters['currency'] = $currency->getId();

        $reports = $this->getRepository()->report($filters);

        return $reports;
    }

    public function getCustomerProductList($filters = [], int $limit = 20, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;
        $report = $this->getReportCustomerRepository()->getCustomerProductReport(
            $filters,
            $limit,
            $offset,
            ['cp.cproduct_id' => 'ASC'],
            ['cp.cproduct_id'],
            ['cp.cproduct_id', 'cp.cproduct_username', 'cp.cproduct_product_id product_id', 'c.customer_currency_id currency_id', 'cu.currency_code', 'cp.cproduct_balance current_balance']
        );
        //$report = $this->getRepository()->getCustomerProductReport($filters, $limit, $offset);
        $customerProductIds = array_map(
            function ($customerProduct) {
                return $customerProduct['cproduct_id'];
            },
            $report
        );

        $beforeDate = \DateTime::createFromFormat('Y-m-d', $filters['from'])->modify('-1 day')->format('Y-m-d');
        $beforeTheFromDateReport = $this->reportModifyKeys($this->getReportCustomerRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], null, $beforeDate, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
        $afterDate = \DateTime::createFromFormat('Y-m-d', $filters['to'])->modify('+1 day')->format('Y-m-d');
        $afterTheToDateReport = $this->reportModifyKeys($this->getReportCustomerRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $afterDate, null, ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
        $rangeDateReport = $this->reportModifyKeys($this->getReportCustomerRepository()->computeCustomerProductsTotalTransactions(['customerProductIds' => $customerProductIds], $filters['from'], $filters['to'], ['st.subtransaction_customer_product_id'], [], ['st.subtransaction_customer_product_id AS cproduct_id']));
        $report = array_map(
            function ($customerProduct) use ($beforeTheFromDateReport, $afterTheToDateReport, $rangeDateReport) {
                $customerProduct['before'] = $beforeTheFromDateReport[$customerProduct['cproduct_id']] ?? [
                    'cproduct_id' => $customerProduct['cproduct_id'],
                    'deposit' => 0,
                    'withdraw' => 0,
                    'winloss' => 0,
                    'bet' => 0,
                    'total' => 0,
                ];
                $customerProduct['after'] = $afterTheToDateReport[$customerProduct['cproduct_id']] ?? [
                    'cproduct_id' => $customerProduct['cproduct_id'],
                    'deposit' => 0,
                    'withdraw' => 0,
                    'winloss' => 0,
                    'bet' => 0,
                    'total' => 0,
                ];
                $customerProduct['range'] = $rangeDateReport[$customerProduct['cproduct_id']] ?? [
                    'cproduct_id' => $customerProduct['cproduct_id'],
                    'deposit' => 0,
                    'withdraw' => 0,
                    'winloss' => 0,
                    'bet' => 0,
                    'total' => 0,
                ];

                return $customerProduct;
            },
            $report
        );

        $totalFilteredCustomerProducts = $this->getCustomerProductRepository()->getCustomerProductListFilterCount([
            'products' => $filters['products'],
            'currencies' => array_merge($filters['currencies'] ?? [], [$filters['currency']]),
            'search' => $filters['search'] ?? '',
        ]);

        $totalCustomerProducts = $this->getCustomerProductRepository()->getCustomerProductListFilterCount([
            'products' => $filters['products'],
            'currencies' => array_merge($filters['currencies'] ?? [], [$filters['currency']]),
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

    public function getCustomerProductDWL(int $customerProductId, array $filters, int $limit = 20, int $page = 1)
    {
        $offset = ($page - 1) * $limit;
        $report = $this->getRepository()->getCustomerProductDWL($customerProductId, $filters, $limit, $offset);
        $total = $this->getRepository()->getCustomerProductDWLTotal($customerProductId, $filters);

        return [
            'records' => $report,
            'recordsFiltered' => $total,
            'recordsTotal' => $total,
            'limit' => $limit,
            'page' => $page,
        ];
    }

    protected function getRepository(): \ReportBundle\Repository\ReportProductRepository
    {
        return $this->getContainer()->get('report_product.repository');
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

    private function getReportCustomerRepository(): \ReportBundle\Repository\ReportCustomerRepository
    {
        return $this->getContainer()->get('report_customer.repository');
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\Currency::class);
    }

    private function getCustomerProductRepository(): \DbBundle\Repository\CustomerProductRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\CustomerProduct::class);
    }

    public function getCurrencyByCode(String $currencyCode): Currency
    {
        return $this->getDoctrine()->getRepository(Currency::class)->findOneByCode($currencyCode);
    }

    public function printProductsCsvReport(Currency $currency,\DateTimeInterface $reportStartAt, \DateTimeInterface $reportEndAt, String $productSearchString = null)
    {
        // filters
        echo "From: ". $reportStartAt->format('Y-m-d') ."\n";
        echo "To: ". $reportEndAt->format('Y-m-d') ."\n";
        echo "Currency: ". $currency->getCode() ."\n";

        if ($productSearchString !== null && $productSearchString !== '') {
            echo "Product Search: ". $productSearchString ." \n";
        }
        echo "\n";


        // headers
        echo 'Product,';
        echo 'No. of Sign Ups,';
        echo 'No. of Newly Opened Accounts,';
        echo 'No. of Signups w/o deposit,';
        echo 'Total No. of Registered Accounts,';
        echo 'No. Active Accounts,';
        echo 'Turnover,';
        echo 'Win/Loss,';
        echo 'Gross Comm.';
        echo "\n";


        $filters = $this->generateTemporaryFilters( $currency, $reportStartAt,  $reportEndAt,  $productSearchString);
        $reports = $this->getReportProductList($filters);

        $totalSignups = 0;
        $totalNewAccounts = 0;
        $totalSignupsWithoutDeposit = 0;
        $totalRegistrations = 0;
        $totalActiveAccounts = 0;
        $totalTurnover = 0;
        $totalWinloss = 0;
        $totalGrossCommission = 0;

        foreach ($reports as $report) {
            echo $report["product_name"] . ',';
            echo $report["num_sign_ups"] . ',';
            echo $report["num_new_accounts"] . ',';
            echo $report["num_signups_wo_deposit"] . ',';
            echo $report["total_register"] . ',';
            echo $report["num_active_accounts"] . ',';
            echo $report["turnover"] . ',';
            echo $report["win_loss"] . ',';
            echo $report["gross_commission"] . ',';

            echo "\n";

            $totalSignups += $report["num_sign_ups"];
            $totalNewAccounts += $report["num_new_accounts"];
            $totalSignupsWithoutDeposit += $report["num_signups_wo_deposit"];
            $totalRegistrations += $report["total_register"];
            $totalActiveAccounts += $report["num_active_accounts"];
            $totalTurnover += $report["turnover"];
            $totalWinloss += $report["win_loss"];
            $totalGrossCommission += $report["gross_commission"];
        }

        // footer
        echo 'Total,';
        echo $totalSignups . ',';
        echo $totalNewAccounts . ',';
        echo $totalSignupsWithoutDeposit . ',';
        echo $totalRegistrations . ',';
        echo $totalActiveAccounts . ',';
        echo $totalTurnover . ',';
        echo $totalWinloss . ',';
        echo $totalGrossCommission . ',';

    }

    /**
     * this temporary converts proper parameters into the array format used by existing report methods
     * this will be just used to be able to reuse
     *
     * @return Array [
     *      'to' => date('Y-m-d')
     *      'from' => date('Y-m-d')
     *      'currency' => String[EUR,GBP,BTC]
     *      'search' => String
     * ]
     */
    private function generateTemporaryFilters(Currency $currency,\DateTimeInterface $reportStartAt, \DateTimeInterface $reportEndAt, String $productSearchString = null): Array
    {
        $filters = [];
        $filters['from'] = $reportStartAt->format('Y-m-d');
        $filters['to'] = $reportEndAt->format('Y-m-d');
        $filters['currency'] = $currency->getCode();
        if ($productSearchString !== null) {
            $filters['search'] = $productSearchString;
        }

        return $filters;
    }
}
