<?php

use Page\InactiveMemberListPage;
use Step\Acceptance\Admin as AdminAcceptanceTester;


class InactiveMemberCest
{
    public function _before(AdminAcceptanceTester $I)
    {
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    // tests
    public function tryToViewPageWithEmptyRecords(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $I->amOnPage(InactiveMemberListPage::$URL);
        $I->see(InactiveMemberListPage::$pageTitle);
        $I->waitForText('No data available in table');

        $I->see('No data available in table');
    }

    public function tryToUpdateList(AdminAcceptanceTester $I)
    {
        $inactiveListPage = new InactiveMemberListPage($I);

        $I->loginAsAdmin();
        $I->amOnPage(InactiveMemberListPage::$URL);
        $I->see(InactiveMemberListPage::$updateListButton);
        $I->click(InactiveMemberListPage::$updateListButton);
        $I->waitForText(InactiveMemberListPage::$removeMemberButton);

        $I->see(InactiveMemberListPage::$previousPageButton);
        $I->see(InactiveMemberListPage::$nextPageButton);

        $I->wantTo('reupdate the list to make sure that it can override existing values');
        $dateGenerated = $inactiveListPage->getDateMemberWasListedInactiveOnRow(1);
        $I->wait(60);
        $inactiveListPage->updateList();
        $I->waitForText(InactiveMemberListPage::$loadingIndicator);
        $I->wait(30);
        $I->waitForElementNotVisible(InactiveMemberListPage::$loadingIndicator);
        $dateUpdated = $inactiveListPage->getDateMemberWasListedInactiveOnRow(1);

        $I->assertTrue($dateGenerated != $dateUpdated);
    }

    public function tryToRemoveMember(AdminAcceptanceTester $I)
    {
        $inactiveListPage = new InactiveMemberListPage($I);

        $I->loginAsAdmin();
        $I->amOnPage(InactiveMemberListPage::$URL);
        $I->see(InactiveMemberListPage::$updateListButton);
        $I->click(InactiveMemberListPage::$updateListButton);
        $I->waitForText(InactiveMemberListPage::$removeMemberButton);

        $userUnderTest = 'Alice Wonderland';
        $I->see($userUnderTest);
        $inactiveListPage->removeMember($userUnderTest);
        $I->waitForText(InactiveMemberListPage::$successModalTitle, 30);
        $I->see('Member ('. $userUnderTest .') has been removed');
        $I->click(InactiveMemberListPage::$successMemberRemovalCloseModalButton);
        $I->wait(2);

        $I->dontSee(InactiveMemberListPage::$successModalTitle);
        $I->dontSee($userUnderTest);
    }

    public function tryToSearchMemberOnList(AdminAcceptanceTester $I)
    {
        $inactiveListPage = new InactiveMemberListPage($I);

        $I->loginAsAdmin();
        $I->amOnPage(InactiveMemberListPage::$URL);
        $I->see(InactiveMemberListPage::$updateListButton);
        $I->click(InactiveMemberListPage::$updateListButton);
        $I->waitForText(InactiveMemberListPage::$removeMemberButton);
        $inactiveListPage->searchMember('Ponyo Brunhilde');

        $I->waitForText('Showing 1 to 1 of 1 entries');
    }

    public function tryToSearchMemberNotOnList(AdminAcceptanceTester $I)
    {
        $inactiveListPage = new InactiveMemberListPage($I);

        $I->loginAsAdmin();
        $I->amOnPage(InactiveMemberListPage::$URL);
        $I->see(InactiveMemberListPage::$updateListButton);
        $I->click(InactiveMemberListPage::$updateListButton);

        $inactiveListPage->searchMember('Omega Shenron');

        $I->waitForText('No data available in table');
        $I->see('No data available in table');
    }

}
