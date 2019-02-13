<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\SettingPage;


class SettingsUpdateBitcoinCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToUpdateBitcoinSetting(AdminAcceptanceTester $I)
    {
        $newBitcoinSetting = [
            'BitcoinSetting_autoDecline' => true, 
            'BitcoinSetting_minutesInterval' => 21, 
            'BitcoinSetting_minimumAllowedDeposit' => 1.11, 
            'BitcoinSetting_maximumAllowedDeposit' => 9.11,
        ];
        $settingPage = new SettingPage($I);
        $I->loginAsAdmin();
        $I->amOnPage($settingPage::$URL);
        $I->waitForText($settingPage::$bitcoinFormTitle);
        $bitcounDefaultSettingResult = $settingPage->getBitcoinCurrentSettings();
        $settingPage->switchAutoDecline($newBitcoinSetting['BitcoinSetting_autoDecline']);
        $settingPage->fillMinutesInterval($newBitcoinSetting['BitcoinSetting_minutesInterval']);
        $settingPage->fillMinimumAllowedDeposit($newBitcoinSetting['BitcoinSetting_minimumAllowedDeposit']);
        $settingPage->fillMaximumAllowedDeposit($newBitcoinSetting['BitcoinSetting_maximumAllowedDeposit']);
        $settingPage->submitBitcoinSetting();
        $I->waitForText('Saved');
        $bitcounDefaultSettingSavedResult = $settingPage->getBitcoinCurrentSettings();
        
        $isSettingsApplied = $settingPage->isSettingsApplied($bitcounDefaultSettingSavedResult['values'], [
            $newBitcoinSetting['BitcoinSetting_autoDecline'],
            $newBitcoinSetting['BitcoinSetting_minutesInterval'],
            $newBitcoinSetting['BitcoinSetting_minimumAllowedDeposit'],
            $newBitcoinSetting['BitcoinSetting_maximumAllowedDeposit'],
        ]);
        
        if (!$isSettingsApplied) {
            $I->see('this test fails... new bitcoin settings did not apply');
        }
        
        $I->amOnPage($settingPage::$URL);
        $I->waitForText($settingPage::$bitcoinFormTitle);
        $status = false;
        $bitcounDefaultSettingResult = $settingPage->getBitcoinCurrentSettings();
        $settingPage->switchAutoDecline($status);
        $settingPage->submitBitcoinSetting();
        $I->waitForText('Saved');
        
        $checkboxAutoDeclineStatus = $settingPage->getAutoDeclineStatus($settingPage::$autoDeclineId);
        $isSettingsApplied = $settingPage->isSettingsApplied([$status], [$checkboxAutoDeclineStatus]);

        if (!$isSettingsApplied) {
            $I->see('this test fails... auto decline switchery did not apply');
        }        
    }    
}
