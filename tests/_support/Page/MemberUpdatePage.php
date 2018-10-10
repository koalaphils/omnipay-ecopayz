<?php
namespace Page;


class MemberUpdatePage extends MemberCreatePage
{
    private $id;
    
    // include url of current page
    public static $URL = '/members/*/profile';
    public static $baseUrl = '/app_test.php/en/members/(\d+)/(\w+)';
    public static $profileUrl = '/app_test.php/en/members/(\d+)/profile';
    public static $productUrl = '/app_test.php/en/members/(\d+)/product';

    public static $formTitle = 'Update Member';
    public static $profileTabHeader = 'Member Details';
    public static $profileTabMarketingHeader = 'Marketing Info';

    public static $turnoverCommissionWinLossHeader = 'Total Member W/L';
    public static $turnoverCommissionTurnoverHeader = 'Total Turnover';
    public static $turnoverCommissionAffiliateCommissionHeader = 'Affiliate Commission';

    public static $productsTab = '//*[@id="wrapper"]/div[3]/div/div/div/div[2]/div/div/ul/li[5]/a';
    public static $affiliateSettingTab = '//*[@id="wrapper"]/div[3]/div/div/div/div[2]/div/div/ul/li[6]/a';
    public static $turnoverCommissionByProductTab = '//*[@id="commissions"]/div/div/div/div/ul/li[3]/a';
    public static $turnoverCommissionByMemberIdTab = '//*[@id="commissions"]/div/div/div/div/ul/li[4]/a';

    public static $acWalletLabel = 'AC Wallet';
    public static $activeProductLabel = 'Active';

    public static $productsTableACWalletRowProductNameColumn = '//*[@id="DataTables_Table_0"]/tbody/tr[td//text()[contains(., \'AC Wallet\')]]/td[2]';
    public static $productsTableACWalletRowProductStatusColumn = '//*[@id="DataTables_Table_0"]/tbody/tr[td//text()[contains(., \'AC Wallet\')]]/td[4]';

    public static $addProductButton = '//*[@id="addProduct"]';
    
    public static $memberListURL = 'members/';
    public static $pikachiProductButton = '#DataTables_Table_0 > tbody > tr:nth-child(1) > td:nth-child(8) > a.btn.btn-icon.waves-effect.waves-light.btn-warning.btn-xs.btn-icn.active-state-action > i';
    public static $productUsernameField = '//*[@id="update_productForm_data_username"]';
    public static $productField = '#update_productForm_data > div.modal-dialog.modal-responsive > div > div > div.panel-body.p-20 > div:nth-child(2) > div > div > div > button > span.filter-option.pull-left';
    public static $balanceAmountField = '#update_productForm_data_balance';
    public static $saveProductButton = '#update_productForm_data_btnSave';
    public static $saveButtonLabel = 'Save';

    public static $profileButton = '#DataTables_Table_0 > tbody > tr > td:nth-child(8) > a.btn.btn-primary.waves-effect.waves-light.btn-xs > i';
    public static $tagsField = '//*[@id="update_profileForm_data"]/div[1]/div[1]/div/div/div[2]/div/div/div[6]/div[2]/div/button/span[1]';
    public static $affiliateTag = '#update_profileForm_data > div.row > div:nth-child(1) > div > div > div:nth-child(2) > div > div > div:nth-child(6) > div.col-md-8 > div > div > ul > li:nth-child(1) > a > span.text';
    public static $saveProfileButton = '#update_profileForm_data_btnSave';
    
    public static $referralToolsTab = '//*[@id="wrapper"]/div[3]/div/div/div/div[2]/div/div/ul/li[11]/a/span[2]';
    public static $referralCodeButton = '#btnCreateReferralName';
    public static $referralAddReferralCodeLabel = 'Add Referral Code'; 
    public static $referralWebsitesLabel = 'Websites';
    public static $referralCodesLabel = 'Referral Codes';
    public static $referralCampaignNamesLabel = 'Campaign Name';
    public static $referralBannersLabel = 'Banners';
    public static $addReferralCodeTitle = 'ADD REFERRAL CODE';
    public static $referralToolsTabLabel = 'Referral Tools';
    public static $referralCodeField = '//*[@id="update_createMemberReferralNameForm_data_name"]';
    public static $saveReferralCodeButton = '#update_createMemberReferralNameForm_data_btnSave';
    
    public static $affiliateFIeld = '//*[@id="update_profileForm_data"]/div[1]/div[1]/div/div/div[2]/div/div/div[2]/div[2]/span[1]/span[1]/span';
    public static $affiliateInputField = 'body > span > span > span.select2-search.select2-search--dropdown > input';
    public static $picachiUsername = 'pikachi';
    
    public static $memberListSearchField = '//*[@id="index_list_container"]/div[2]/div[2]/div/input';
    public static $turnoverCommissionDateCoveredLabel = 'Date Covered';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    public function getId(): string
    {
        if (is_null($this->id)) {
            $this->id =  $this->tester->grabFromCurrentUrl('#^' . self::$baseUrl . '#');
        }
        
        return $this->id;
    }
    
    public function gotoProductPage(): void
    {
        $this->tester->amOnPage('/members/' . $this->getId() . '/product');
        $this->tester->waitForElement(self::$addProductButton, 20);
    }
    
    public function getBalanceForUsername(string $username): string
    {
        try {
            $this->tester->seeCurrentUrlMatches('#^' . self::$productUrl . '#');
        } catch (Exception $ex) {
            $this->gotoProductPage();
        }
        
        $this->tester->waitForText($username, 20);
        return $this->tester->grabTextFrom("descendant-or-self::td[contains(., '" . $username . "')]/parent::tr/td[3]");
    }
    
    public function seeBalanceForUsername(string $username, string $balance)
    {
        try {
            $this->tester->seeCurrentUrlMatches('#^' . self::$productUrl . '#');
        } catch (Exception $ex) {
            $this->gotoProductPage();
        }
        $this->tester->waitForText($username, 20);
        $this->tester->see($balance, "descendant-or-self::td[contains(., '" . $username . "')]/parent::tr/td[3]");
    }

    public function selectProdutButtonFromList(): void
    {
        $this->tester->wait(3);
        $this->tester->click(self::$pikachiProductButton);
        $this->tester->waitForText(self::$formTitle);
    }
    
    public function clickProfileButtonFromList(): void
    {
        $this->tester->click(self::$profileButton);
        $this->tester->waitForText(self::$formTitle);
    }
    
    public function searchFullNameToList(string $fullName = ''): void
    {
        $this->tester->fillField(self::$memberListSearchField, $fullName);
    }

    public function selectTagAffiliate(): void
    {
        $this->tester->click(self::$tagsField);
        $this->tester->wait(1);
        $this->tester->click(self::$affiliateTag);
    }

    public function clickAddProductButton(): void
    {
        $this->tester->click(self::$addProductButton);
        $this->tester->wait(1);
    }

    public function setUsername(string $username = ''): void
    {
        $this->tester->fillField(self::$productUsernameField, $username);
    }

    public function selectProduct(string $product = ''): void
    {
        $this->tester->click(self::$productField);
        $this->tester->wait(1);
        $this->tester->click($product);
    }

    public function setBalance(int $balanceAmount = 100): void
    {
        $this->tester->fillField(self::$balanceAmountField, $balanceAmount);
    }

    public function goToTabWithLabel(string $tabName = ''): void
    {
        $this->tester->click($tabName);
        $this->tester->wait(1);
    }
    
    public function addReferralCode(): void
    {
        $this->tester->scrollTo(self::$referralCodeButton);
        $this->tester->see(self::$referralCodesLabel);
        $this->tester->click(self::$referralCodeButton);
        $this->tester->wait(1);
    }

    public function saveReferralCode(): void
    {
        $this->tester->scrollTo(self::$saveReferralCodeButton);
        $this->tester->see(self::$saveButtonLabel);
        $this->tester->click(self::$saveReferralCodeButton);
    }
    
    public function fillReferralCodeField(string $codeName = ''): void
    {
        $this->tester->fillField(self::$referralCodeField, $codeName);
    }
    
    public function searchAffiliateFieldByReferralCode(string $referralCode = ''): void
    {
        $this->tester->click(self::$affiliateFIeld);
        $this->tester->wait(1);
        $this->tester->fillField(self::$affiliateInputField, $referralCode);
    }
    
    public function searchAffiliateFieldByFullName(string $fullName = ''): void
    {
        $this->tester->wait(1);
        $this->tester->fillField(self::$affiliateInputField, $fullName);
    }

    public function saveProduct(): void
    {
        $this->tester->scrollTo(self::$saveProductButton);
        $this->tester->see(self::$saveButtonLabel);
        $this->tester->click(self::$saveProductButton);
    }
    
    public function saveProfile(): void
    {
        $this->tester->scrollTo(self::$saveProfileButton);
        $this->tester->see(self::$saveButtonLabel);
        $this->tester->click(self::$saveProfileButton);
    }
}
