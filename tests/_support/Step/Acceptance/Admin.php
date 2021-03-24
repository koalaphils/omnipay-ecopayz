<?php
namespace Step\Acceptance;
use \Codeception\Step\Argument\PasswordArgument;
use Page\LoginPage;

class Admin extends \AcceptanceTester
{

    public function loginAsAdmin()
    {
        $I = $this;
        if ($I->loadSessionSnapshot('login')) {
            return;
        }
        $username = 'admin';
        $password = 'm$zqe72&EmI';
        $loginPage = new LoginPage($I);
        $loginPage->submitLoginCredentials($username, new PasswordArgument($password));
        $I->saveSessionSnapshot('login');
    }

}