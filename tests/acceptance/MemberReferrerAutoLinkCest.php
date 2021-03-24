<?php

use Codeception\Example;
use Page\MemberListPage;
use Page\MemberUpdatePage;
use Step\Acceptance\Admin as AdminAcceptanceTester;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\User;

class MemberReferrerAutoLinkCest
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
    public function tryToLinkMemberToReferrerByReferrerCode(AdminAcceptanceTester $I, Example $example)
    {
        $memberUsername = $example['memberUsername'];

        $I->wantToTest('the linking of referral to its referrer using the referrer code on registration');
        $memberReferralName = $this->assignAReferralCodeFromAReferrerToAMember($I, $memberUsername);
        $referrer = $memberReferralName->getMember();

        $I->amOnPage(MemberListPage::$URL);

        $memberListPage = new MemberListPage($I);
        $memberListPage->goToProfileForUsername($memberUsername);

        $I->click(MemberUpdatePage::$autoLinkReferrerButton);
        $I->waitForText(MemberUpdatePage::$profileLinkMemberHeader, 5);
        $I->click(MemberUpdatePage::$linkMemberButton);
        $I->waitForText(MemberUpdatePage::$profileLinkMemberSuccessfulHeader, 10);
        $I->click(MemberUpdatePage::$linkMemberOkButton);
        $I->wait(3);
        $I->dontSee(MemberUpdatePage::$profileLinkMemberSuccessfulHeader);
        $I->seeOptionIsSelected(MemberUpdatePage::$referrerField, sprintf('%s (%s)', $referrer->getFullName(), $referrer->getUsername()));
    }

    private function assignAReferralCodeFromAReferrerToAMember(AdminAcceptanceTester $I, string $username): MemberReferralName
    {
        $memberReferralName = $I->have(MemberReferralName::class);
        $user = $I->have(User::class, [
            'username' => $username,
            'preferences' => ['affiliateCode' => $memberReferralName->getName()],
        ]);
        $member = $I->have(Member::class, [
            'user' => $user,
            'fullName' => $username,
        ]);

        $I->seeInDatabase('member_referral_name', [
            'member_referral_name_id' => $memberReferralName->getId(),
            'member_referral_name_name' => $memberReferralName->getName(),
        ]);
        $I->seeInDatabase('user', [
            'user_id' => $user->getId(),
            'user_username' => $username,
        ]);
        $I->seeInDatabase('customer', [
            'customer_id' => $member->getId(),
            'customer_user_id' => $user->getId(),
        ]);

        return $memberReferralName;
    }

    private function memberDataProvider()
    {
        return [[
            'memberUsername' => 'patamon',
        ]];
    }
}
