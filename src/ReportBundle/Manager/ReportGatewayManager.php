<?php

namespace ReportBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Gateway;
use GatewayBundle\Manager\GatewayManager;

class ReportGatewayManager extends AbstractManager
{
    private $gatewayManager;

    public function __construct(GatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;
    }

    public function getReportPaymentGatewayList($filters = null)
    {
        if (!array_has($filters, 'gateways')) {
            $gateways = $this->getGatewayRepository()->getGatewayList([
                'currencyCode' => $filters['currency'],
                'search' => $filters['search']
            ]);
            $gatewayIds = [];
            foreach ($gateways as $gateway) {
                $gatewayIds[] = $gateway['id'];
            }
            unset($filters['currency']);
            $filters['gateways'] = $gatewayIds;
        }

        $reports = array_map(function ($report) use ($filters) {
            $report['link'] = $this->getRouter()->generate(
                'report.gateway_transactions',
                ['id' => $report['gateway_id'], 'filters' => ['from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? '']]
            );

            return $report;
        }, $this->getRepository()->report($filters));

        return $reports;
    }

    public function printGatewayCsvReport(?array $filters = null)
    {
        $currencyCode = $filters['currency'];
        if (!array_has($filters, 'gateways')) {
            $gateways = $this->getGatewayRepository()->getGatewayList([
                'currencyCode' => $filters['currency'],
                'search' => $filters['search']
            ]);
            $gatewayIds = [];
            foreach ($gateways as $gateway) {
                $gatewayIds[] = $gateway['id'];
            }
            unset($filters['currency']);
            $filters['gateways'] = $gatewayIds;
        }

        $reports = $this->getRepository()->report($filters);
        // todo: search by ?/?


        // filter details
        echo 'Currency: ' . $currencyCode . "\n";
        echo 'From: ' . (new \DateTimeImmutable($filters['from']))->format('F d, Y') . "\n";
        echo 'To: ' .  (new \DateTimeImmutable($filters['to']))->format('F d, Y') . "\n";
        echo "\n";

        echo 'Payment Gateway,';
        echo '# of Deposits,';
        echo '# of Withdraws,';
        echo 'Total Deposit,';
        echo 'Total Withdraw,';
        echo 'Total Company Fee,';
        echo 'Total Member Fee';
        echo "\n";

        $totalDepositTransactionsCount = 0;
        $totalWithdrawalTransactionsCount = 0;
        $totalDepositAmount = 0;
        $totalWithdrawalAmount = 0;
        $totalCompanyFees = 0;
        $totalCustomerFees = 0;

        foreach ($reports as $report) {
            echo $report["gateway_name"] . ',';
            echo $report["num_deposits"] . ',';
            echo $report["num_withdraws"] . ',';
            echo $report["sum_deposits"] . ',';
            echo abs($report["sum_withdraws"]) . ',';
            echo $report["sum_company_fees"] . ',';
            echo $report["sum_customer_fees"] . ',';
            echo "\n";

            $totalDepositTransactionsCount += $report["num_deposits"];
            $totalWithdrawalTransactionsCount += $report["num_withdraws"];
            $totalDepositAmount += $report["sum_deposits"];
            $totalWithdrawalAmount += abs($report["sum_withdraws"]);
            $totalCompanyFees += $report["sum_company_fees"];
            $totalCustomerFees += $report["sum_customer_fees"];
        }

        // footer
        echo "\n";
        echo 'Total,';
        echo $totalDepositTransactionsCount . ',';
        echo $totalWithdrawalTransactionsCount . ',';
        echo $totalDepositAmount . ',';
        echo $totalWithdrawalAmount . ',';
        echo $totalCompanyFees . ',';
        echo $totalCustomerFees . ',';
    }

    public function getGatewayTransactionReportFileName(int $gatewayId, array $filters): String
    {
        $gateway = $this->getGatewayManager()->findOneById($gatewayId);
        $reportStartFrom = new \DateTimeImmutable($filters['from']);
        $reportEndFrom = new \DateTimeImmutable($filters['to']);
        $filename = $gateway->getName().'_'. $gateway->getCurrency()->getCode(). '_'. $reportStartFrom->format('Ymd') .'_'. $reportEndFrom->format('Ymd') .'.csv';

        return $filename;
    }

    public function getGatewayReportFileName(Currency $currency, array $filters): String
    {
        $reportStartFrom = new \DateTimeImmutable($filters['from']);
        $reportEndFrom = new \DateTimeImmutable($filters['to']);
        $filename = 'Gateways_'. $currency->getCode(). '_'. $reportStartFrom->format('Ymd') .'_'. $reportEndFrom->format('Ymd') .'.csv';

        return $filename;
    }

    protected function getRepository(): \ReportBundle\Repository\ReportGatewayRepository
    {
        return $this->getContainer()->get('report_gateway.repository');
    }

    private function getGatewayRepository(): \DbBundle\Repository\GatewayRepository
    {
        return $this->getDoctrine()->getRepository(Gateway::class);
    }

    public function findCurrencyByCode(String $currencyCode): Currency
    {
        return $this->getEntityManager()->getRepository(Currency::class)->findOneByCode($currencyCode);
    }

    private function getGatewayManager(): GatewayManager
    {
        return $this->gatewayManager;
    }
}
