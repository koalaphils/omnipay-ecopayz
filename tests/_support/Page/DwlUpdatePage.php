<?php

namespace Page;

class DwlUpdatePage extends PageObject
{
    public static $statusContainer = '.dwl-status';
    public static $statusUploadedText = 'Uploaded';
    public static $statusSubmitedText = 'Submitted';
    public static $pageHeader = 'Update DWL';
    public static $submitButton = '#btnSubmit';
    
    public function submitDwl()
    {
        $this->tester->seeElement(self::$submitButton);
        $this->tester->click(self::$submitButton);
    }
    
    public function seeUploaded()
    {
        $this->tester->reloadPage();
        $this->tester->waitForText(self::$statusUploadedText, 20, self::$statusContainer);
    }
    
    public function seeSubmited()
    {
        $this->tester->reloadPage();
        $this->tester->waitForText(self::$statusSubmitedText, 20, self::$statusContainer);
    }
    
    public function grabAmountForUsername(string $username)
    {
        $this->tester->waitForText($username, 20);
        
        return $this->tester->grabTextFrom("descendant-or-self::td[contains(., '" . $username . "')]/parent::tr/td[6]");
    }
}
