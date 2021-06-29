<?php

use Codeception\Example as TestData;
use Codeception\Util\Debug;
use Step\Acceptance\Admin as AdminAcceptanceTester;
use Page\MemberListPage;

class MemberSearchCest
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
    public function tryToSearchMember(AdminAcceptanceTester $I, TestData $testData): void
    {
        $memberListPage = new MemberListPage($I);
        $I->amOnPage($memberListPage::$URL);
        foreach ($testData as $key => $data) {
            $keyword = $data['keyword'];
            $expectedResult = $data['result'];
            $memberListPage->searchByKeyword($keyword);
            $I->waitForText($expectedResult);
        }
    }
    
    private function memberDataProvider(): array
    {
        return [
            [
                ['result' => 'June 29, 2018', 'keyword' =>'Registrant Full name 20180629_061823'], //fullname
                ['result' => 'Ethiopia', 'keyword' => 'Registrant Full name 20180629_061903'], //fullname
                ['result' => 'Registrant Full name 20180629_061823', 'keyword' => '20180629_061823'],  //lastName
                ['result' => 'Registrant Full name 20180629_061903', 'keyword' => '20180629_061903'], //lastName
                ['result' => 'Daniel Rodriguez Torres', 'keyword' => 'Daniel'], //First Name
                ['result' => 'Noelle Stiller', 'keyword' => 'Noelle'], //First Name
                ['result' => 'Registrant Full name 20180629_061903', 'keyword' => 'RegistrationTest_20180629_061903@localhost.com'], //email address
                ['result' => 'Registrant Full name 20180629_091308' ,'keyword' => 'RegistrationTest_20180629_091308@localhost.com'], //email address
                ['result' => 'Registrant Full name 20180629_091308', 'keyword' => 'RegistrationTest_20180629_091308'], //product username
                ['result' => 'Registrant Full name 20180629_061823', 'keyword' => 'RegistrationTest_20180629_061823'], //product username
                ['result' => 'December 2, 2017', 'keyword' => 'pikachu'], //username
                ['result' => 'Albania', 'keyword' => 'pikachi'], //username
            ],
        ];
    }
}
