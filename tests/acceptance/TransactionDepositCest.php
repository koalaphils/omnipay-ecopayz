<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\TransactionCreatePage;

class TransactionDepositCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateDeposit(AdminAcceptanceTester $I)
    {

        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage(TransactionCreatePage::$URL);
        $I->click(TransactionCreatePage::$createTransactionButton);
        $I->click(TransactionCreatePage::$createTransactionDepositButton);
        $I->waitForText(TransactionCreatePage::$formTitleDeposit);


        $transactionCreatePage->setMemberByUsername('pikachu');
        $transactionCreatePage->selectPaymentOption('NETELLER (pika_neteller@localpokeball.com)');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->setProductAndAmount('AsianOdds (ac_pikapika)', 15);
        $transactionCreatePage->setFeesAndNote($customerFee = 10, $companyFee = 10, $note = '');
        $transactionCreatePage->submitTransaction();
        // assertions

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');


    }

    public function tryToCreateDepositWithMultipleProduct(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage(TransactionCreatePage::$URL);
        $I->click(TransactionCreatePage::$createTransactionButton);
        $I->click(TransactionCreatePage::$createTransactionDepositButton);
        $I->waitForText(TransactionCreatePage::$formTitleDeposit);


        $transactionCreatePage->setMemberByUsername('pikachu');
        $transactionCreatePage->selectPaymentOption('NETELLER (pika_neteller@localpokeball.com)');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->addRandomProductsOnDeposit([10, 15]);
        $transactionCreatePage->setFeesAndNote($customerFee = 10, $companyFee = 10, get_class());
        $transactionCreatePage->submitTransaction();
        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }
}
