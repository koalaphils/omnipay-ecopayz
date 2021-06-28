<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use \Page\Reports\PaymentGatewayReportPage;
use \Page\Reports\SinglePaymentGatewayReportPage;
use \Page\Reports\ProductsReportPage;
use \Page\Reports\SingleProductReportPage;
use \Page\Reports\MembersReportPage;

class ReportsCest
{
    public function _before(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function tryToTestAllPaymentGatewayReports(AdminAcceptanceTester $I)
    {
        $paymentGatewayReportPage = new PaymentGatewayReportPage($I);
        $I->amOnPage($paymentGatewayReportPage::$URL);
        $I->see($paymentGatewayReportPage::$pageTitle);

        $paymentGatewayReportPage->submitForm('02/01/2018', '09/01/2018','EURO');

        $I->waitForText('NT-MAIN');
        $I->see('NT-MAIN');
        $I->see('NT-TURKISH');
        $I->see('MB-PERSONAL');
        $firstRowProductName = $paymentGatewayReportPage->getProductNameAtRow(1);
        $firstRowProductTotalDeposits = $paymentGatewayReportPage->getTotalDepositsAtRow(1);
        $I->assertSame('NT-MAIN 2', $firstRowProductName);
        $I->assertSame('6,810.00', $firstRowProductTotalDeposits);
    }

    public function tryToTestAllPaymentGatewayReportsExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllPaymentGatewayReports($I);
        $I->click(PaymentGatewayReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('Total,9,0,7254,0,0,0,');
        $I->see('Total,9,0,7254,0,0,0,');
    }

    public function tryToTestSinglePaymentGatewayReport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllPaymentGatewayReports($I);
        $singlePaymentGatewayReportPage = new SinglePaymentGatewayReportPage($I);
        $I->click('NT-MAIN 2');
        $I->switchToNextTab();
        $I->waitForText($singlePaymentGatewayReportPage::$pageTitle);
        $I->see($singlePaymentGatewayReportPage::$pageTitle);

        $I->waitForText('20180629-100313-1');
        $firstRowTransactionNumber= $singlePaymentGatewayReportPage->getTransactionNumberAtRow(1);
        $firstRowTransactionDate = $singlePaymentGatewayReportPage->getTransactionDateAtRow(1);
        $firstRowTransactionAmount = $singlePaymentGatewayReportPage->getTransactionAmountAtRow(1);

        $I->assertSame('20180629-100313-1', $firstRowTransactionNumber);
        $I->assertSame('Jun 29, 2018 10:03 AM', $firstRowTransactionDate);
        $I->assertSame('1009.00', $firstRowTransactionAmount);
    }

    public function tryToTestSinglePaymentGatewayReportExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestSinglePaymentGatewayReport($I);
        $I->click(SinglePaymentGatewayReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('Processed,Processed, " Number, Date, Member, Currency, Amount, Type');
        $I->see('Processed,Processed, " Number, Date, Member, Currency, Amount, Type');
    }

    public function tryToTestSinglePaymentGateway_viewATransaction(AdminAcceptanceTester $I)
    {
        $this->tryToTestSinglePaymentGatewayReport($I);

        $I->click('20180629-100313-1');
        $I->switchToNextTab();
        $I->waitForText('Request Deposit Transaction');
        $I->see('Request Deposit Transaction');
    }

    public function tryToTestAllProductsReport(AdminAcceptanceTester $I)
    {
        $productsReportPage = new ProductsReportPage($I);
        $I->amOnPage($productsReportPage::$URL);
        $I->waitForText($productsReportPage::$pageTitle);

        $productsReportPage->submitForm('02/01/2018', '09/01/2018', 'EURO');
        $I->waitForText('AsianOdds');

        $firstRowProductName = $productsReportPage->getProductNameAtRow(1);
        $firstRowProductTurnover = $productsReportPage->getProductTurnoverAtRow(1);
        $firstRowProductWinloss = $productsReportPage->getProductWinlossAtRow(1);
        $firstRowProductGrossCommission = $productsReportPage->getProductGrossCommissionAtRow(1);

        $I->assertSame('AsianOdds', $firstRowProductName);
        $I->assertSame('52,926,387.00', $firstRowProductTurnover);
        $I->assertSame('-723,047.52', $firstRowProductWinloss);
        $I->assertSame('205,985.43', $firstRowProductGrossCommission);
    }

    public function tryToTestAllProductsReportExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllProductsReport($I);
        $I->click(ProductsReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('From: 2018-02-01 To: 2018-09-01 Currency: EUR');
        $I->see('From: 2018-02-01 To: 2018-09-01 Currency: EUR');
    }

    public function tryToTestSingleProductsReport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllProductsReport($I);
        $I->click('AsianOdds');
        $I->switchToNextTab();
        $I->waitForText('ac_pikapika');
        $I->see('AsianOdds EUR Report - ( Feb 01, 2018 to Sep 01, 2018 )');

        $singleProductReportPage = new SingleProductReportPage($I);

        $firstRowMemberProductName = $singleProductReportPage->getMemberProductNameAtRow(1);
        $firstRowMemberProductBalanceAsOf = $singleProductReportPage->getMemberProductBalanceAsOfDateAtRow(1);
        $firstRowMemberProductCurrentBalance = $singleProductReportPage->getMemberProductCurrentBalanceAtRow(1);

        $I->assertSame('ac_pikapika', ($firstRowMemberProductName));
        $I->assertSame('1007143.00', ($firstRowMemberProductBalanceAsOf));
        $I->assertSame('1007143.00', ($firstRowMemberProductCurrentBalance));

    }

    public function tryToTestSingleProductsReportExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestSingleProductsReport($I);
        $I->click(SingleProductReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('"ac_pikapika",0,0,0,0,1007143');
        $I->see('"ac_pikapika",0,0,0,0,1007143');
    }

    public function tryToTestAllMembersReport(AdminAcceptanceTester $I)
    {
        $membersReportPage = new MembersReportPage($I);
        $I->amOnPage($membersReportPage::$URL);
        $membersReportPage->submitForm('02/01/2018', '09/01/2018', 'EURO','Show All Records');
        $I->waitForText('pikachu');

        $secondRowMemberName = $membersReportPage->getMemberNameAtRow(2);
        $I->assertSame('pikachu', $secondRowMemberName);
    }

    public function tryToTestAllMembersReportExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllMembersReport($I);
        $I->click(MembersReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('From: 2018-02-01 To: 2018-09-01 Currency: EUR ');
        $I->see('From: 2018-02-01 To: 2018-09-01 Currency: EUR ');
    }

    public function tryToTestSingleMembersReport(AdminAcceptanceTester $I)
    {
        $this->tryToTestAllMembersReport($I);
        $I->click('pikachu');
        $I->switchToNextTab();
        $I->waitForText('ac_pikapika');
        $I->see('pikachu Report - ( Feb 01, 2018 to Sep 01, 2018 )');
        $membersReportPage = new MembersReportPage($I);
        $firstRowMemberProductName = $membersReportPage->getMemberNameAtRow(1);

        $I->assertSame('ac_pikapika', $firstRowMemberProductName);
    }

    public function tryToTestAllSingleReportExport(AdminAcceptanceTester $I)
    {
        $this->tryToTestSingleMembersReport($I);
        $I->click(MembersReportPage::$exportButton);
        $I->switchToNextTab();

        $I->waitForText('ac_pikapika');
        $I->see('ac_pikapika');

    }
}
