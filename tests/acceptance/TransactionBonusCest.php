<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\TransactionCreatePage;

class TransactionBonusCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateBonus(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionBonusButton);
        $I->waitForText($transactionCreatePage::$bonusFormTitle);
        
        $transactionCreatePage->setMemberByFullName('pikachu');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->setProductAndAmount('AsianOdds (ac_pikapika)', 2);
        $transactionCreatePage->setNote('Bonus Transaction');

        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
        $I->makeScreenshot('Bonus');
    }
    
    public function tryToCreateBonusWithMultipleProduct(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionBonusButton);
        $I->waitForText($transactionCreatePage::$bonusFormTitle);
        
        $transactionCreatePage->setMemberByFullName('pikachu');
        $transactionCreatePage->selectFirstPaymentGateway();
        $transactionCreatePage->addRandomProductsOnDeposit([10, 15]);
        $transactionCreatePage->setNote(get_class());
        
        $I->makeScreenshot();

        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
        $I->makeScreenshot('Bonus');
    }
}
