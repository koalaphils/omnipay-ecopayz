<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\TransactionCreatePage;

class TransactionP2PCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateP2P(AdminAcceptanceTester $I)
    {
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionP2PButton);
        $I->waitForText($transactionCreatePage::$p2pFormTitle);
        
        $transactionCreatePage->setMemberFromP2P('pikachu');
        $transactionCreatePage->setMemberToP2P('pikachi');
        $transactionCreatePage->setTransferProductAndAmount('AsianOdds (ac_pikapika)', 'BetISN (pika_betisn)', 2, 2, 'Test P2P Transfer');
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }

    public function tryToCreateP2PWithMultipleProduct(AdminAcceptanceTester $I)
    {
        $amounts = [10,10];
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($transactionCreatePage::$URL);
        $I->click($transactionCreatePage::$createTransactionButton);
        $I->click($transactionCreatePage::$createTransactionP2PButton);
        $I->waitForText($transactionCreatePage::$p2pFormTitle);
        
        $transactionCreatePage->setMemberFromP2P('pikachu');
        $transactionCreatePage->setMemberToP2P('pikachi');
        $transactionCreatePage->setMultipleProductWithAmountsForSender($amounts);
        $transactionCreatePage->setMultipleProductWithAmountsForReceiver($amounts);
        $transactionCreatePage->submitTransaction();

        $I->waitForText('Saved');
        $I->wait(3);
        $I->waitForElementNotVisible('After Balance');
        $I->dontSee('After Balance');
    }
}
