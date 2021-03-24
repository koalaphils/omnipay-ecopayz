<?php

namespace Page\Reports;


class SingleProductReportPage extends ReportPageObject
{
    // include url of current page
    public static $URL = '/reports/products';

    public static $pageTitle = 'Products Reports';
    private static $productNameCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(1)';
    private static $productBalanceAsOfCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(6)';
    private static $productCurrentBalanceCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(7)';

    public function getMemberProductNameAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productNameCell, $row)));
    }

    public function getMemberProductBalanceAsOfDateAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productBalanceAsOfCell, $row)));
    }

    public function getMemberProductCurrentBalanceAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productCurrentBalanceCell, $row)));
    }

}
