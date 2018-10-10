<?php

use AppBundle\ValueObject\Number;
use Codeception\Example;
use Page\DwlListPage;
use Page\DwlUpdatePage;
use Page\MemberListPage;
use Page\MemberUpdatePage;
use Step\Acceptance\Admin as AdminAcceptanceTester;
use Symfony\Component\Process\Process;

class DWLUploadSubmitCest
{
    private $jobProcess;
    
    public function _before(AdminAcceptanceTester $I)
    {
        $this->jobProcess = new Process([
            'php',
            'app/console',
            'jms-job-queue:run',
            '--env=test',
            '--verbose',
        ]);
        $this->jobProcess->start();
        $I->comment('I Started a job queue: ' . $this->jobProcess->getPid());
    }
    
    public function _after(AdminAcceptanceTester $I)
    {
        $this->jobProcess->stop();
        $I->comment('I stop the job queue');
    }
    
    /**
     * @dataProvider uploadDataProvider
     */
    public function tryToUploadAndSubmit(AdminAcceptanceTester $I, Example $example)
    {
        $I->wantToTest('the uploading and submitting of daily win loss');
        
        $memberUsername = $example['memberUsername'];
        $memberProductUsername = $example['memberProductUsername'];
        $currency = $example['currency'];
        $product = $example['product'];
        $dateNow = $example['date'];
        $file = $example['file'];
        
        $I->loginAsAdmin();
        
        $I->comment('I will check first the current balance of member product');
        $I->amOnPage(MemberListPage::$URL);
        
        $memberListPage = new MemberListPage($I);
        $memberListPage->goToProfileForUsername($memberUsername);
        
        $memberUpdatePage = new MemberUpdatePage($I);
        $memberUpdatePage->gotoProductPage();
        $currentBalance = $memberUpdatePage->getBalanceForUsername($memberProductUsername);
        
        $I->comment('I want to test the uploading of daily win loss');
        $I->amOnPage(DwlListPage::$URL);
        $dwlListPage = new DwlListPage($I);
        $dwlListPage->openAddDwlModal();
        $dwlListPage->fillUpTheDWLModal($product, $currency, $dateNow->format('m/d/Y'), $file);
        $dwlListPage->submitForm();
        $I->waitForText(DwlListPage::$uploadedText, 20);
        
        $I->waitForText(DwlUpdatePage::$pageHeader, 20);
        $I->see($dateNow->format('m/d/Y'));
        $I->see($product);
        $dwlUpdatePage = new DwlUpdatePage($I);
        $I->wait(5);
        $dwlUpdatePage->seeUploaded();
        
        $I->comment('I want to test the submitting of daily win loss');
        $dwlUpdatePage->submitDwl();
        $I->wait(5);
        $dwlUpdatePage->seeSubmited();
        $amount = $dwlUpdatePage->grabAmountForUsername($memberProductUsername);
        
        $expectedBalance = Number::add($amount, $currentBalance);
        $expectedBalance = Number::format($expectedBalance->toString(), ['precision' => 10]);
        
        $I->comment('I will confirm if the balance of member product was added');
        $I->openNewTab();
        $memberUpdatePage->gotoProductPage();
        $memberUpdatePage->seeBalanceForUsername($memberProductUsername, $expectedBalance);
        $I->closeTab();
    }
    
    protected function uploadDataProvider()
    {
        return [[
            'memberUsername' => 'pikachi',
            'memberProductUsername' => 'pikachi_ao',
            'currency' => 'EURO',
            'product' => 'AsianOdds',
            'date' => new DateTime('now'),
            'file' => 'test-dwl-upload-1.csv',
        ]];
    }
}
