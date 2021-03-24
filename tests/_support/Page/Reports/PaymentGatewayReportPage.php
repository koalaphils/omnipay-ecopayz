<?php

namespace Page\Reports;


class PaymentGatewayReportPage extends ReportPageObject
{
    // include url of current page
    public static $URL = '/reports/gateways';

    public static $pageTitle = 'Payment Gateway';
    private static $productNameCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(1)';
    private static $productTotalDepositsCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(4)';


    public function getProductNameAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productNameCell, $row)));
    }

    public function getTotalDepositsAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productTotalDepositsCell, $row)));
    }
}
