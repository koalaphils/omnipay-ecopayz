<?php

namespace Page\Reports;


class SinglePaymentGatewayReportPage extends ReportPageObject
{
    public static $pageTitle = 'Gateway Transaction Report';

    private static $transactionNumberCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(1)';
    private static $transactionDateCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(2)';
    private static $transactionAmountCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(5)';

    public function getTransactionNumberAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$transactionNumberCell, $row)));
    }

    public function getTransactionDateAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$transactionDateCell, $row)));
    }

    public function getTransactionAmountAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$transactionAmountCell, $row)));
    }

}
