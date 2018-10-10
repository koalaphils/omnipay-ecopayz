<?php

use Codeception\Example;
use Page\MemberListPage;
use Page\MemberUpdatePage;
use Step\Acceptance\Admin as AdminAcceptanceTester;

class MemberTurnoverCommissionCest
{
    public function _before(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(AdminAcceptanceTester $I)
    {
    }

    /**
     * @dataProvider memberDataProvider
     */
    public function tryToViewTurnoverAndCommissionGroupByProductOfMember(AdminAcceptanceTester $I, Example $example)
    {
        $I->wantToTest('the turnover/commission table group by product of a member');

        $memberUsername = $example['memberUsername'];

        $I->amOnPage(MemberListPage::$URL);

        $memberListPage = new MemberListPage($I);
        $memberListPage->goToProfileForUsername($memberUsername);

        $I->wait(5);
        $I->click(MemberUpdatePage::$affiliateSettingTab);
        $I->click(MemberUpdatePage::$turnoverCommissionByProductTab);
        $this->seeTurnoverCommissionTableHeaders($I);
    }

    /**
     * @dataProvider memberDataProvider
     */
    public function tryToViewTurnoverAndCommissionGroupByMemberIdOfMember(AdminAcceptanceTester $I, Example $example)
    {
        $I->wantToTest('the turnover/commission table group by member ID of a member');

        $memberUsername = $example['memberUsername'];

        $I->amOnPage(MemberListPage::$URL);

        $memberListPage = new MemberListPage($I);
        $memberListPage->goToProfileForUsername($memberUsername);

        $I->wait(5);
        $I->click(MemberUpdatePage::$affiliateSettingTab);
        $I->click(MemberUpdatePage::$turnoverCommissionByMemberIdTab);
        $this->seeTurnoverCommissionTableHeaders($I);
    }

    private function seeTurnoverCommissionTableHeaders(AdminAcceptanceTester $I)
    {
        $I->see(MemberUpdatePage::$turnoverCommissionWinLossHeader);
        $I->see(MemberUpdatePage::$turnoverCommissionAffiliateCommissionHeader);
        $I->see(MemberUpdatePage::$turnoverCommissionTurnoverHeader);
        $I->waitForText(MemberUpdatePage::$turnoverCommissionDateCoveredLabel, 15);
    }

    protected function memberDataProvider()
    {
        return [[
            'memberUsername' => 'pikachi',
        ]];
    }
}
