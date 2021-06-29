<?php
namespace Page;

class InactiveMemberListPage extends PageObject
{
    // include url of current page
    public static $URL = '/members/inactive-members/';
    public static $pageTitle = 'Inactive Members (no activity for last 6 months)';

    public static $updateListButton = 'Update List';
    public static $removeMemberButton = 'Remove';
    public static $previousPageButton = 'Previous';
    public static $nextPageButton = 'Next';
    public static $dateMemberWasListedInactiveCell = '#datatable-responsive > tbody > tr:nth-child(%d) > td:nth-child(6)';
    public static $removeSpecificMemberButton = '//button[@data-member-name="%s"]';
    public static $loadingIndicator = 'Processing';
    public static $successModalTitle = 'Success';
    public static $confirmMemberRemovalButton = 'Yes';
    public static $successMemberRemovalCloseModalButton = 'OK';
    public static $memberSearchField = '//input[@name="search"]';

    public function getDateMemberWasListedInactiveOnRow(int $row)
    {
        return $this->tester->grabTextFrom(sprintf(self::$dateMemberWasListedInactiveCell,$row));
    }

    public function updateList()
    {
        $this->tester->click(self::$updateListButton);
    }

    public function removeMember($memberName)
    {
        $this->tester->click(sprintf(self::$removeSpecificMemberButton, $memberName));
        $this->tester->waitForText('Remove From Inactive List');
        $this->tester->see('Remove From Inactive List');
        $this->tester->wait(1);
        $this->tester->click(self::$confirmMemberRemovalButton);
    }

    public function searchMember(string $memberName)
    {
        $this->tester->fillField(self::$memberSearchField,$memberName);
    }
}
