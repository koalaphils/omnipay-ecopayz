<?php

namespace Page\Reports;


class ProductsReportPage extends ReportPageObject
{
    // include url of current page
    public static $URL = '/reports/products';

    public static $pageTitle = 'Products Reports';
    private static $productNameCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(1)';
    private static $productTurnOverCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(8)';
    private static $productWinlossCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(9)';
    private static $productGrossCommissionCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(10)';

    public function getProductNameAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productNameCell, $row)));
    }

    public function getProductTurnoverAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productTurnOverCell, $row)));
    }

    public function getProductWinlossAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productWinlossCell, $row)));
    }

    public function getProductGrossCommissionAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$productGrossCommissionCell, $row)));
    }

}
