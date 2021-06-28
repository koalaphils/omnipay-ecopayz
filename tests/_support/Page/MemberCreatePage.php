<?php
namespace Page;


class MemberCreatePage extends PageObject
{
    // include url of current page
    public static $URL = '/members/create';

    public static $formTitle = 'Create Member';
    public static $backToMemberListButton = 'Back to Member List';

    public static $form = 'form';
    public static $userNameField = '#create_createForm_data_username';
    public static $passwordField = '//*[@id="create_createForm_data_password"]';
    public static $confirmPasswordFieldLabel = 'Confirm Password';
    public static $confirmPasswordField = '//*[@id="create_createForm_data_confirmPassword"]';
    public static $emailField = '//*[@id="create_createForm_data_email"]';
    public static $fullNameField = '//*[@id="create_createForm_data_fullName"]';

    public static $currencyDropDownField = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[4]/div/div/div/button';
    public static $currencyDropDownOptionEuro = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[4]/div/div/div/div/ul/li[4]/a';
    public static $countryDropDownField = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[3]/div/div/div/button/span[1]';
    public static $countryDropDownOptionEthiopia = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[3]/div/div/div/div/ul/li[69]/a';
    public static $birthDateField = '#create_createForm_data_birthDate';
    public static $dateJoinedField = '#create_createForm_data_joinedAt';
    public static $groupDropDownField = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[6]/div/div/div/button';
    public static $groupDropDownOptionGroupAll = '//*[@id="create_createForm_data"]/div[1]/div/div/div/div[2]/div/div/div[6]/div/div/div/div/ul/li[1]/a';
    public static $saveButton = 'Save';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    public function selectEuroAsCurrency()
    {
        $this->tester->click(self::$currencyDropDownField);
        $this->tester->click(self::$currencyDropDownOptionEuro);
    }

    public function selectEthiopiaAsCountry()
    {
        $this->tester->click(self::$countryDropDownField);
        $this->tester->click(self::$countryDropDownOptionEthiopia);
    }

    public function setBirthdate($birthDate)
    {
        // workaround for bootstrap datepicker
        $this->tester->executeJS('$("'. self::$birthDateField .'").datepicker("setDate", new Date("' .  date ( 'Y-m-d' ,strtotime ( '-20 years' , strtotime ( $birthDate ) ) ) . '"));');
        $this->tester->executeJS('$("'. self::$birthDateField .'").datepicker(\'hide\')');
    }

    public function setDateJoined($dateJoined)
    {
        $this->tester->executeJS('$("'. self::$dateJoinedField .'").datepicker("setDate", new Date("' . date ( 'm/j/Y g:i:s A' , strtotime ( $dateJoined ) ) . '"));');
        $this->tester->executeJS('$("'. self::$dateJoinedField .'").datepicker(\'hide\')');
        $this->tester->fillField(self::$dateJoinedField , date ( 'm/j/Y g:i:s A' , strtotime ( $dateJoined ) ));
    }

    public function selectDefaultGroup()
    {
        $this->tester->click(self::$groupDropDownField);
        $this->tester->click(self::$groupDropDownOptionGroupAll);
        // click the dropdown again to close the selection
        $this->tester->click(self::$groupDropDownField);
    }


}
