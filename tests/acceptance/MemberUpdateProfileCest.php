<?php

use Codeception\Example as TestData;
use Codeception\Util\Debug;
use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\MemberUpdatePage;

class MemberUpdateProfileCest
{
    public function _before(AdminAcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(AdminAcceptanceTester $I)
    {

    }

    public function tryToUpdateMemberProfileByTaggingAsAffiliate(AcceptanceTester $I): void
    {
        $targetAffiliate = 'pikachi';
        $memberUpdatePage = new MemberUpdatePage($I);
        $I->amOnPage($memberUpdatePage::$memberListURL);
        $memberUpdatePage->searchFullNameToList($targetAffiliate);
        $I->waitForText($targetAffiliate);
        
        $memberUpdatePage->clickProfileButtonFromList();
        $memberUpdatePage->selectTagAffiliate();
        $memberUpdatePage->saveProfile();
        $I->waitForText('Saved');
    }

    /**
     * @dataProvider memberDataProvider
     */
    public function tryToUpdateMemberProfileByAddingReferralCodeToReferralToolTab(AcceptanceTester $I, TestData $testData): void
    {
        $referralCode = $testData['referralCode'];
        $targetAffiliate = 'pikachi';
        $memberUpdatePage = new MemberUpdatePage($I);
        $I->amOnPage($memberUpdatePage::$memberListURL);
        $memberUpdatePage->searchFullNameToList($targetAffiliate);
        $I->waitForText($targetAffiliate);
        
        $memberUpdatePage->clickProfileButtonFromList();
        $memberUpdatePage->goToTabWithLabel($memberUpdatePage::$referralToolsTabLabel);
        $I->see($memberUpdatePage::$referralWebsitesLabel);
        $I->see($memberUpdatePage::$referralCodesLabel);
        $I->see($memberUpdatePage::$referralCampaignNamesLabel);
        $I->see($memberUpdatePage::$referralBannersLabel);
        
        $memberUpdatePage->goToTabWithLabel($memberUpdatePage::$referralCodesLabel);
        $I->see($memberUpdatePage::$referralAddReferralCodeLabel);
        $memberUpdatePage->addReferralCode();
        $I->see($memberUpdatePage::$addReferralCodeTitle);
        $memberUpdatePage->fillReferralCodeField($referralCode);
        $memberUpdatePage->saveReferralCode();
        $I->waitForText('Saved');
    }
  
    /**
     * @dataProvider memberDataProvider
     */
    public function tryToUpdateTagOfaMemberAndSearchIntoAffiliateField(AcceptanceTester $I, TestData $testData): void
    {
        $memberFullName = $testData['memberFullName'];
        $referralCode = $testData['referralCode'];
        //update profile tag into affiliate
        $targetAffiliate = 'pikachi';
        $targetMember = 'pikachu';
        $memberUpdatePage = new MemberUpdatePage($I);
        $I->amOnPage($memberUpdatePage::$memberListURL);
        $memberUpdatePage->searchFullNameToList($targetAffiliate);
        $I->waitForText($targetAffiliate);
        
        $memberUpdatePage->clickProfileButtonFromList();
        $memberUpdatePage->selectTagAffiliate();
        $memberUpdatePage->saveProfile();
        $I->waitForText('Saved');
        
        //add referral code to affiliate
        $memberUpdatePage->goToTabWithLabel($memberUpdatePage::$referralToolsTabLabel);
        $I->see($memberUpdatePage::$referralWebsitesLabel);
        $I->see($memberUpdatePage::$referralCodesLabel);
        $I->see($memberUpdatePage::$referralCampaignNamesLabel);
        $I->see($memberUpdatePage::$referralBannersLabel);
        
        $memberUpdatePage->goToTabWithLabel($memberUpdatePage::$referralCodesLabel);
        $I->see($memberUpdatePage::$referralAddReferralCodeLabel);
        $memberUpdatePage->addReferralCode();
        $I->see($memberUpdatePage::$addReferralCodeTitle);
        $memberUpdatePage->fillReferralCodeField($referralCode);
        $memberUpdatePage->saveReferralCode();
        $I->waitForText('Saved');

        //search pikachi and kamote to affiliate field.
        $I->amOnPage($memberUpdatePage::$memberListURL);
        $memberUpdatePage->searchFullNameToList($targetMember);
        $I->waitForText($targetMember);
        
        $memberUpdatePage->clickProfileButtonFromList();
        $memberUpdatePage->searchAffiliateFieldByReferralCode($referralCode);
        $I->waitForText($memberUpdatePage::$picachiUsername);
        $memberUpdatePage->searchAffiliateFieldByFullName($memberFullName);
        $I->waitForText($memberUpdatePage::$picachiUsername);
    }
    
    private function memberDataProvider(): array
    {
        return [
            [
                'referralCode' => 'kamote', 
                'memberFullName' => 'pikachi'
            ],
        ];
    }
}
