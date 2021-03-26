<?php
namespace Page;

class MemberListPage extends PageObject
{
    // include url of current page
    public static $URL = '/members';

    public static $addMemberButtonLabel = 'Add Member';
    public static $searchField = '.ztable_search_input';
    public static $dataTable = '//*[@id="DataTables_Table_0"]';
    public static $processingLabel = 'Processing';

    public static $memberListSearchField = '//*[@id="index_list_container"]/div[2]/div[2]/div/input';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }
    
    public function goToProfileForUsername(string $username): void
    {
        $this->tester->waitForElement(self::$dataTable, 20);
        $this->tester->fillField(self::$searchField, $username);
        $this->tester->waitForText(self::$processingLabel);
        $this->tester->wait(3);
        $this->tester->waitForText($username, 20);
        $this->clickProfileForUsername($username);
    }

    public function clickProfileForUsername(string $username): void
    {
        $this->tester->click(sprintf(
            "%s/descendant-or-self::tr[td//text()[contains(., '%s')]]/td[8]/a[1]",
            self::$dataTable,
            $username
        ));
        $this->tester->waitForText(MemberUpdatePage::$formTitle);
    }

    public function searchByKeyword(string $keyword = ''): void
    {
        $this->tester->fillField(self::$memberListSearchField, $keyword);
    }
}
