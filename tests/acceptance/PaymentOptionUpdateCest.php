<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\SettingPage;
use Page\TransactionCreatePage;

class PaymentOptionUpdateCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToUpdatePaymentOption(AdminAcceptanceTester $I): void
    {
        $bitcoin = 'bitcoin';
        $settingPaymentOptionPage = new SettingPage($I);
        $I->loginAsAdmin();
        $I->amOnPage($settingPaymentOptionPage::$paymentOptionURL);
        $I->waitForText($settingPaymentOptionPage::$paymentOptionFormTitle);
        
        $settingPaymentOptionPage->searchPaymentOption($bitcoin);
        $I->waitForText($settingPaymentOptionPage::$bitcoinAccountText);
        $settingPaymentOptionPage->clickBitcoinEditButton();
        $I->waitForText($settingPaymentOptionPage::$updatePaymentOptionTitle);

        $settingPaymentOptionPage->selectPaymentMode($bitcoin);
        $settingPaymentOptionPage->savePaymentOption();
        $I->waitForText('Saved');
    }

    public function tryToDepositUsingBitcoinPaymentOption(AdminAcceptanceTester $I): void
    {
        $bitcoin = 'bitcoin';
        $settingPaymentOptionPage = new SettingPage($I);
        $I->loginAsAdmin();
        $I->amOnPage($settingPaymentOptionPage::$paymentOptionURL);
        $I->waitForText($settingPaymentOptionPage::$paymentOptionFormTitle);
        
        $settingPaymentOptionPage->searchPaymentOption($bitcoin);
        $I->waitForText($settingPaymentOptionPage::$bitcoinAccountText);
        $settingPaymentOptionPage->clickBitcoinEditButton();
        $I->waitForText($settingPaymentOptionPage::$updatePaymentOptionTitle);

        $settingPaymentOptionPage->selectPaymentMode($bitcoin);
        $settingPaymentOptionPage->savePaymentOption();
        $I->waitForText('Saved');
        
        $transactionCreatePage = new TransactionCreatePage($I);
        $I->amOnPage($transactionCreatePage::$depositURL);
        $I->waitForText($transactionCreatePage::$formTitleDeposit);
        $transactionCreatePage->setMemberByUsername('pikachu');
        $transactionCreatePage->selectPaymentOption('NETELLER (pika_neteller@localpokeball.com)');
        $I->dontSee('Bitcoin Address');
        $I->dontSee('Trans Hash');
        $I->dontSee('Receiver');
        $I->dontSee('Deposit Request Rate');
        $transactionCreatePage->selectPaymentOption('BITCOIN (pika_neteller@localpokeball.com)');
        $I->waitForText('Bitcoin Address');
        $I->see('Trans Hash');
        $I->see('Receiver');
        $I->see('Deposit Request Rate');
    }
}
