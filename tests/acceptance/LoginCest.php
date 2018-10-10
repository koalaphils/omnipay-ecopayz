<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\LoginPage;

class LoginCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    public function tryToLogin(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $I->amOnPage(LoginPage::$URL);
        // Main Asserts / Asserts for the Page Under Test should not be in the Page Objects
        $I->see('Dashboard');
        $I->see('Finance');
        $I->see('Member');
        $I->see('PENDING TRANSACTIONS');
        $I->see('Manage Widgets');

    }
}
