<?php

namespace Page\Reports;


class MembersReportPage extends ReportPageObject
{
    // include url of current page
    public static $URL = '/reports/members';
    public static $pageTitle = 'Payment Gateway';
    private static $memberNameCell = '#DataTables_Table_0 > tbody > tr:nth-child(%d) > td:nth-child(1)';

    public function getMemberNameAtRow(int $row): string
    {
        return trim($this->tester->grabTextFrom(sprintf(self::$memberNameCell, $row)));
    }
}
