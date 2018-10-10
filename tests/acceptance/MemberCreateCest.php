<?php

use Page\MemberListPage;
use Page\MemberCreatePage;
use Page\MemberUpdatePage;
use Step\Acceptance\Admin as AdminAcceptanceTester;


class MemberCreateCestCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreateMember(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $currentTime = date('Ymd_His');
        $I->wantToTest('the registration of member from backoffice');
        $I->amOnPage(MemberListPage::$URL);
        $I->waitForText(MemberListPage::$addMemberButtonLabel, 20);
        $I->see(MemberListPage::$addMemberButtonLabel);
        $I->click(MemberListPage::$addMemberButtonLabel);
        $I->wait(3);
        $I->see(MemberCreatePage::$formTitle);
        $I->see(MemberCreatePage::$backToMemberListButton);
        $I->see(MemberCreatePage::$confirmPasswordFieldLabel);

        $I->fillField(MemberCreatePage::$userNameField , 'RegistrationTest_' . $currentTime);
        $I->fillField(MemberCreatePage::$passwordField, 'SomeAwesomePassword1234@@');
        $I->fillField(MemberCreatePage::$confirmPasswordField, 'SomeAwesomePassword1234@@');
        $I->fillField(MemberCreatePage::$emailField, 'RegistrationTest_' . $currentTime . '@localhost.com');
        $I->fillField(MemberCreatePage::$fullNameField, 'Registrant Full name ' . $currentTime);

        $memberCreatePage = new MemberCreatePage($I);
        $memberCreatePage->selectEuroAsCurrency();
        $memberCreatePage->selectEthiopiaAsCountry();
        $twentyYearsFromToday = date ( 'Y-m-d' ,strtotime ( '-20 years' , strtotime ( date('Ymd') ) ) );
        $dateJoined = date('m/j/Y g:i:s A');
        $memberCreatePage->setBirthdate($twentyYearsFromToday);
        $memberCreatePage->setDateJoined($dateJoined);
        $memberCreatePage->selectDefaultGroup();

        $I->see(MemberCreatePage::$saveButton);
        $I->submitForm(MemberCreatePage::$form,[]);
        $I->waitForText(MemberUpdatePage::$profileTabHeader, 15);
        $I->waitForText(MemberUpdatePage::$profileTabMarketingHeader, 15);

        // when u see these labels, it means that you have been redirected to member update, which means that member registration has been successful
        $I->see(MemberUpdatePage::$profileTabHeader);
        $I->see(MemberUpdatePage::$profileTabMarketingHeader);
        $I->wait(20);

        $I->wantToTest('the automatic creation of AC Wallet');
        $I->click(MemberUpdatePage::$productsTab);
        $I->waitForText(MemberUpdatePage::$acWalletLabel, 40);
        $I->see(MemberUpdatePage::$acWalletLabel);
        $I->see(MemberUpdatePage::$acWalletLabel, MemberUpdatePage::$productsTableACWalletRowProductNameColumn);
        $I->see(MemberUpdatePage::$activeProductLabel, MemberUpdatePage::$productsTableACWalletRowProductStatusColumn);


    }

}
