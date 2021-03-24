<?php

use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\MemberUpdatePage;

class MemberUpdateAddProductsCest
{
    public function _before(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(AdminAcceptanceTester $I)
    {

    }

    // tests
    public function tryToUpdateMemberByAddingProducts(AdminAcceptanceTester $I)
    {
        $memberUpdatePage = new MemberUpdatePage($I);
        $I->loginAsAdmin();
        $I->amOnPage($memberUpdatePage::$memberListURL);
        //first product
        $memberUpdatePage->selectProdutButtonFromList();
        $memberUpdatePage->clickAddProductButton();
        $memberUpdatePage->setUsername('usermaxbet');
        $memberUpdatePage->selectProduct('Maxbet (MXB)');
        $memberUpdatePage->setBalance(100);
        $memberUpdatePage->saveProduct();
        $I->waitForText('Saved');
        //secod product
        $memberUpdatePage->clickAddProductButton();
        $memberUpdatePage->setUsername('usermatchbook');
        $memberUpdatePage->selectProduct('Matchbook (MTB)');
        $memberUpdatePage->setBalance(99);
        $memberUpdatePage->saveProduct();
        $I->waitForText('Saved');
    }
}
