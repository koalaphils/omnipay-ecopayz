<?php

namespace Page;

use Codeception\Module\WebDriver;
use Codeception\Util\Locator;

class DwlListPage extends PageObject
{
    public static $URL = "dwl/";
    public static $addDwlBtnLabel = "Add DWL";
    public static $addDwlBtn = "Add DWL";
    public static $modalUploadTitle = "UPLOAD DWL";
    public static $modal = '//*[@id="formModal"]';
    
    public static $productSelect = '//*[@id="DWLUpload_product"]';
    public static $currencySelect = '//*[@id="DWLUpload_currency"]';
    
    public static $dateField = '//*[@id="DWLUpload_date"]';
    public static $fileField = '//*[@id="DWLUpload_file"]';
    
    public static $saveBtn = '//*[@id="DWLUpload_save"]';
    public static $uploadedText = 'Uploaded';
    
    public static $fileToUpload = 'test-dwl-upload-1.csv';
    
    public function openAddDwlModal(): void
    {
        $this->tester->waitForText(self::$addDwlBtnLabel, 20);
        $this->tester->see(self::$addDwlBtnLabel);
        $this->tester->click(self::$addDwlBtn);
        $this->tester->waitForText(self::$modalUploadTitle, 20);
    }
    
    public function fillUpTheDWLModal(string $product, string $currency, string $date, string $file)
    {
        $this->select2SelectItem(self::$productSelect, $product);
        $this->select2SelectItem(self::$currencySelect, $currency);
        $this->tester->fillField(self::$dateField, $date);
        $this->tester->attachFile(self::$fileField, $file);
    }
    
    public function submitForm()
    {
        $this->tester->click(self::$saveBtn);
    }
}
