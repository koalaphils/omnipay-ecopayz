<?php

namespace Page;
use \Codeception\Step\Argument\PasswordArgument;

class LoginPage extends PageObject
{
    // include url of current page
    public static $URL = '/';

    public static $usernameField = '_username';
    public static $passwordField = '_password';
    public static $loginButtonLabel = 'LOG IN';



    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    public function submitLoginCredentials($username, $password)
    {
        $this->tester->amOnPage(LoginPage::$URL);
        // auxiliary asserts are OK on ReusableObjects like pageObjects
        $this->tester->see(LoginPage::$loginButtonLabel);

        $this->tester->submitForm('form', [
            #formElement => value
            LoginPage::$usernameField => $username,
            LoginPage::$passwordField => $password
        ]);
        $this->tester->wait(3);
    }

}
