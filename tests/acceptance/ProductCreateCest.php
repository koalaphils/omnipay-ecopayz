<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\ProductCreatePage;

class ProductCreateCest
{
    public function _before(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(AdminAcceptanceTester $I)
    {

    }

    // tests
    public function tryToCreateProduct(AdminAcceptanceTester $I)
    {
        // replace values with unique data or enable cleanup per test
        $I->amOnPage(ProductCreatePage::$URL);
        $I->click(ProductCreatePage::$addProductButton);
        $I->waitForText(ProductCreatePage::$createProductLink);
        $I->fillField(ProductCreatePage::$productCodeField, 'CPD');
        $I->fillField(ProductCreatePage::$productNameField, 'CPDD');
        $I->fillField(ProductCreatePage::$productUrlField, 'http://cpDD');

        $I->click(ProductCreatePage::$saveButton);
        $I->waitForText(ProductCreatePage::$successNotificationLabel);
    }
}
