<?php

namespace Page\Reports;

use Page\PageObject;

class ReportPageObject extends PageObject
{

    private static $reportStartDateField = '#dateFrom';
    private static $reportEndDateField = '#dateTo';
    private static $currencyField = '//button[@data-id="currency"]';
    private static $hideZeroValueRecordsField = '//button[@data-id="hideZeroValueRecords"]';

    private static $filterButton = 'Filter';

    public static $exportButton = 'Export to CSV';
    private static $loadingPrompt = 'Processing';

    public function submitForm(string $reportStartDate, string $reportEndDate, string $currencyName, ?string $hideZeroValuesOption = null)
    {
        $this->tester->fillField(self::$reportStartDateField, $reportStartDate);
        $this->tester->fillField(self::$reportEndDateField, $reportEndDate);
        $this->tester->click(self::$currencyField);
        $this->tester->click($currencyName);
        $this->tester->click('h4');
        if ($hideZeroValuesOption != null) {
            $this->tester->click(self::$hideZeroValueRecordsField);
            $this->tester->click($hideZeroValuesOption);
            $this->tester->click('h4');
        }

        $this->tester->click(self::$filterButton);
        $this->tester->wait(2);
    }
}
