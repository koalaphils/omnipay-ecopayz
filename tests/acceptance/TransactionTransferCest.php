<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\TransactionCreatePage;
use Page\MemberUpdatePage;

class TransactionTransferCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateTransfer(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionTransferButton);
        $I->waitForText($transactionCreatePage::$transferFormTitle);
        
        $transactionCreatePage->setMemberByFullName("pikachu");
        $transactionCreatePage->setTransferProductAndAmount('AsianOdds (ac_pikapika)', 'BetISN (pika_betisn)', 2, 2, 'Test Transfer');
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }
    
    public function tryToCreateTransferWithMultipleProducts(AdminAcceptanceTester $I)
    {
        $memberUpdatePage = new MemberUpdatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($memberUpdatePage::$memberListURL);
        //first product
        $memberUpdatePage->selectProdutButtonFromList();
        $memberUpdatePage->clickAddProductButton();
        $memberUpdatePage->setUsername('usermaxbettwo');
        $memberUpdatePage->selectProduct('Maxbet (MXB)');
        $memberUpdatePage->setBalance(200);
        $memberUpdatePage->saveProduct();
        $I->waitForText('Saved');
        //secod product
        $memberUpdatePage->clickAddProductButton();
        $memberUpdatePage->setUsername('usermatchbooktwo');
        $memberUpdatePage->selectProduct('Matchbook (MTB)');
        $memberUpdatePage->setBalance(200);
        $memberUpdatePage->saveProduct();
        $I->waitForText('Saved');
        
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionTransferButton);
        $I->waitForText($transactionCreatePage::$transferFormTitle);
        $transactionCreatePage->setMemberByFullName("pikachi");
        $transactionCreatePage->addFromProductButton();
        $transactionCreatePage->addToProductButton();
        $transactionCreatePage->setFirstProductAndAmountToBeDeducted(100);        
        $transactionCreatePage->setSecondProductAndAmountToBeDeducted(100);
        $transactionCreatePage->setFirstProductAndAmountToBeTransferred(100);
        $transactionCreatePage->setSecondProductAndAmountToBeTransferred(100);
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }
}