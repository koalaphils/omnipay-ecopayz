<?php

namespace Page;

use Codeception\Util\Debug;

class SettingPage extends PageObject
{
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_CHECKBOX = 'checkbox';
    const INPUT_TYPE_NUMBER = 'number';

    public static $URL = '/paymentoptions/BITCOIN/setting';
    public static $bitcoinFormTitle = 'Auto Decline';
    public static $settingMenu = '//*[@id="menu-settings"]/a/span[1]';
    public static $settingBitcoinLink = '//*[@id="menu-settings"]/ul/li[7]/a';
    public static $bitcoinCheckbox = '//*[@id="BitcoinSetting"]/div[1]/div/div/span[1]';
    public static $bitcoinCheckboxSelector = '#BitcoinSetting > div:nth-child(1) > div > div > span.switchery.switchery-default > small';
    public static $saveButton = '//*[@id="BitcoinSetting_save"]';
    public static $saveButtonLabel = 'Save';
    public static $textbox = '//*[@id="%s"]';
    public static $autoDeclineId = 'BitcoinSetting_autoDecline';
    
    //payment option
    public static $paymentOptionURL = '/paymentoptions/';
    public static $paymentOptionFormTitle = 'Payment Option';
    public static $paymentOptionSearchBox = '//*[@id="paymentOptions"]/div[2]/div[2]/div/input';
    public static $editButton = '#datatable-responsive > tbody > tr > td:nth-child(4) > a.btn.btn-primary.waves-effect.waves-light.btn-xs';
    public static $profileTab = 'Profile';
    public static $bitcoinAccountText = 'Bitcoin Account';
    public static $updatePaymentOptionTitle = 'Update Payment Option';
    public static $paymenModeSelectBox = '//*[@id="paymentOption_paymentMode"]';
    public static $savePaymentOptionButton = '#btnSave';
    public static $savePaymentOptionButtonLabel = 'Save';

    public static function route($param)
    {
        return static::$URL.$param;
    }

    public function clickBitcoinEditButton(): void
    {
        $this->tester->click('Edit');
    }

    public function searchPaymentOption(string $value): void
    {
        $this->tester->fillField(self::$paymentOptionSearchBox, $value);
        $this->tester->wait(1);
    }
    
    public function selectPaymentMode(string $value): void
    {
        $this->tester->selectOption(self::$paymenModeSelectBox, $value);
    }

    public function switchAutoDecline(bool $status): void
    {
        if ($status) {
            $this->tester->checkOption(self::$bitcoinCheckbox);
        } else {
            $this->tester->executeJs('$("'. self::$bitcoinCheckboxSelector .'").click();');
        }
    }

    public function savePaymentOption(): void
    {
        $this->tester->scrollTo(self::$savePaymentOptionButton);
        $this->tester->see(self::$savePaymentOptionButtonLabel);
        $this->tester->click(self::$savePaymentOptionButton);
    }

    public function fillMinutesInterval(string $value): void
    {
        $this->tester->fillField(sprintf(self::$textbox, 'BitcoinSetting_minutesInterval'), $value);
    }

    public function fillMinimumAllowedDeposit(string $value): void
    {
        $this->tester->fillField(sprintf(self::$textbox, 'BitcoinSetting_minimumAllowedDeposit'), $value);
    }

    public function fillMaximumAllowedDeposit(string $value): void
    {
        $this->tester->fillField(sprintf(self::$textbox, 'BitcoinSetting_maximumAllowedDeposit'), $value);
    }

    public function getBitcoinCurrentSettings(): array
    {
        $elementTypes = $this->getElementContainer('div', 'id', 'BitcoinSetting', '/div/div/div/input' , 'type');
        $elementIds = $this->getElementContainer('div', 'id', 'BitcoinSetting', '/div/div/div/input' , 'id');
        $returnedResult = [];
        foreach ($elementTypes as $index => $type) {
            $returnedResult['types'][] = $elementTypes[$index];
            $returnedResult['ids'][] = $elementIds[$index];
            switch ($type) {
                case self::INPUT_TYPE_CHECKBOX:
                    $autoDeclineStatus = $this->getAutoDeclineStatus($elementIds[$index]);
                    $returnedResult['values'][] = $autoDeclineStatus;
                    break;
                case self::INPUT_TYPE_NUMBER:
                case self::INPUT_TYPE_TEXT:
                    $returnedResult['values'][] = $this->tester->grabValueFrom('#' . $elementIds[$index]);
                    break;
            }
        }

        return $returnedResult;
    }

    public function submitBitcoinSetting()
    {
        $this->tester->scrollTo(self::$saveButton);
        $this->tester->see(self::$saveButtonLabel);
        $this->tester->click(self::$saveButtonLabel);
    }

    public function isSettingsApplied($appliedSettings, $postSettings): bool
    {
        foreach ($appliedSettings as $key => $value) {
            if ($value != $postSettings[$key]) {
                return false;
            }
        }
        
        return true;
    }

    public function getAutoDeclineStatus(string $checkboxId): bool
    {
        $hasChecked = $this->tester->grabAttributeFrom('#' . $checkboxId, 'checked');

        return empty($hasChecked) ? false : true;
    }

    private function getElementContainer(
        string $element,
        string $type,
        string $query,
        string $lookUp,
        string $returnIndex): 
        array {
        
        return $this->tester->grabMultiple("//" . $element . "[@" . $type . "='". $query ."']" . $lookUp, $returnIndex);
    }    
}
