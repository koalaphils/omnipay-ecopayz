<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\TransactionCreatePage;

class TransactionWithdrawalCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateWithdrawal(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionWithdrawButton);
        $I->waitForText($transactionCreatePage::$withDrawformTitle);
        
        $transactionCreatePage->setMemberByFullName("pikachu");
        $transactionCreatePage->selectPaymentOption('NETELLER (pika_neteller@localpokeball.com)');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->setProductAndAmountOnWithdrawal('AsianOdds (ac_pikapika)', rand(10,1500));
        $transactionCreatePage->setFeesAndNote($customerFee = 10, $companyFee = 10, 'Test1');
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }

    public function tryToCreateWithdrawalWithMultipleProduct(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionWithdrawButton);
        $I->waitForText($transactionCreatePage::$withDrawformTitle);
        
        $transactionCreatePage->setMemberByFullName("pikachu");
        $transactionCreatePage->selectPaymentOption('NETELLER (pika_neteller@localpokeball.com)');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->addRandomProductsOnWithdrawal([10, 15]);
        $transactionCreatePage->setFeesAndNote($customerFee = 10, $companyFee = 10, get_class());
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }
}