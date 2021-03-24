<?php 

namespace tests\integration\PaymentBundle\Form\Type;

use PaymentBundle\Form\BitcoinSettingType;
use PaymentBundle\Model\Bitcoin\SettingModel;
use Codeception\Test\Unit;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;
use Codeception\Util\Debug;

class BitcoinSettingTypeTest extends Unit
{
    /**
     *
     * @dataProvider getTestSubmitValidData
     */
    public function testSubmitValidData(
        $expectedResult,
        $autoDecline,
        $minutesInterval,
        $minimumAllowedDeposit,
        $maximumAllowedDeposit,
        array $expectedErrorFields = []): void {

        $formData = [
            'autoDecline' => $autoDecline, 
            'minutesInterval' => $minutesInterval, 
            'minimumAllowedDeposit' => $minimumAllowedDeposit,
            'maximumAllowedDeposit' => $maximumAllowedDeposit,
        ];
        
        $validationGroups = ['default', 'bitcoinSetting'];
        $settingModel = new SettingModel();
        $form = $this->getSymfonyFormFactory()->create(BitcoinSettingType::class, $settingModel, [
            'validation_groups' => $validationGroups,
            'csrf_protection' => false,
        ]);
        $form->submit($formData);
        
        $this->assertSame($expectedResult, $form->isValid());
        if ($expectedResult === false) {
            $errorFields = $this->getErrorMessages($form);
            foreach ($expectedErrorFields as $field) {
                $this->assertArrayHasKey($field, $errorFields);
            }
        }
    }

    public function getTestSubmitValidData(): array
    {
        return [
            [true, 'autoDecline' => true, 'minutesInterval' => 13, 'minimumAllowedDeposit' => "2",'maximumAllowedDeposit' => "3"],
            [true, 'autoDecline' => false, 'minutesInterval' => 11, 'minimumAllowedDeposit' => "1.2",'maximumAllowedDeposit' => "12"],
            [true, 'autoDecline' => true, 'minutesInterval' => 20, 'minimumAllowedDeposit' => "3.232323",'maximumAllowedDeposit' => "15.345345"],
            [false, 'autoDecline' => true, 'minutesInterval' => "sdas", 'minimumAllowedDeposit' => "3",'maximumAllowedDeposit' => "3", ['minutesInterval', 'minimumAllowedDeposit']],
            [false, 'autoDecline' => true, 'minutesInterval' => 11, 'minimumAllowedDeposit' => "1.2asdasdasd",'maximumAllowedDeposit' => "12", ['minimumAllowedDeposit']],
            [false, 'autoDecline' => true, 'minutesInterval' => 20, 'minimumAllowedDeposit' => "3.4234234",'maximumAllowedDeposit' => "15.345345345345345345", ['maximumAllowedDeposit']],
            [false, 'autoDecline' => true, 'minutesInterval' => 20, 'minimumAllowedDeposit' => "0",'maximumAllowedDeposit' => "0", ['minimumAllowedDeposit', 'maximumAllowedDeposit']],
        ];
    }

    private function getSymfonyFormFactory(): FormFactory
    {
        return $this->getModule('Symfony')->grabService('form.factory');
    }
    
    private function getErrorMessages(Form $form) {
        $errors = array();

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }
        unset($form);
        return $errors;
    }
}
