<?php 

namespace tests\integration\PaymentBundle\Form\Type;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;

use DbBundle\Entity\BitcoinRateSetting;
use PaymentBundle\Form\BitcoinRateSettingsDTOType;
use PaymentBundle\Model\Bitcoin\BitcoinRateSettingsDTO;

class BitcoinRateSettingsDTOTypeTest extends Unit
{
    /**
     * @dataProvider getValidFormData
     */
    public function testValidSubmission($defaultRateSetting, $nonDefaultRateSettings)
    {
        $bitcoinRateSettingDTOToCompare = new BitcoinRateSettingsDTO($defaultRateSetting);
    
        $form = $this->getSymfonyFormFactory()->create(BitcoinRateSettingsDTOType::class, $bitcoinRateSettingDTOToCompare, [
            'csrf_protection' => false,
        ]);

        $bitcoinRateSettingDTO = new BitcoinRateSettingsDTO($defaultRateSetting);
        $bitcoinRateSettingDTO->setBitcoinRateSettings($nonDefaultRateSettings);

        $formData = $this->createFormData($defaultRateSetting, $nonDefaultRateSettings);
        
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($bitcoinRateSettingDTO, $bitcoinRateSettingDTOToCompare);
    }

    /**
     * @dataProvider getInvalidFormData
     */
    public function testInvalidSubmission($defaultRateSetting, $nonDefaultRateSettings)
    {
        $bitcoinRateSettingDTOToCompare = new BitcoinRateSettingsDTO($defaultRateSetting);

        $form = $this->getSymfonyFormFactory()->create(BitcoinRateSettingsDTOType::class, $bitcoinRateSettingDTOToCompare, [
            'csrf_protection' => false,
        ]);

        $bitcoinRateSettingDTO = new BitcoinRateSettingsDTO($defaultRateSetting);
        $bitcoinRateSettingDTO->setBitcoinRateSettings($nonDefaultRateSettings);

        $formData = $this->createFormData($defaultRateSetting, $nonDefaultRateSettings);
        
        $form->submit($formData);
        
        $this->assertFalse($form->isValid());

        $errors = $this->flattenFormErrors($form);
        $this->assertArrayHasKey('bitcoinRateSettings[0].fixedAdjustment', $errors);
        $this->assertArrayHasKey('bitcoinRateSettings[0].percentageAdjustment', $errors);
        $this->assertArrayHasKey('bitcoinRateSettings[1].fixedAdjustment', $errors);
        $this->assertArrayHasKey('bitcoinRateSettings[1].percentageAdjustment', $errors);

        $errorMessageFixedAdjustment = $errors['bitcoinRateSettings[0].fixedAdjustment'][0];
        $errorPercentageAdjustment = $errors['bitcoinRateSettings[0].percentageAdjustment'][0];

        $this->assertEquals($errorMessageFixedAdjustment, 'Either amount or percentage should not be empty.');
        $this->assertEquals($errorPercentageAdjustment, 'Either amount or percentage should not be empty.');
    }

    private function getSymfonyFormFactory(): FormFactory
    {
        return $this->getModule('Symfony')->grabService('form.factory');
    }

    private function createFormData($defaultRateSetting, $nonDefaultRateSettings): array
    {
        $formData = [
            'defaultRateSetting' => [
                'rangeFrom' => $defaultRateSetting->getRangeFrom(),
                'rangeTo' => $defaultRateSetting->getRangeTo(),
                'fixedAdjustment' => $defaultRateSetting->getFixedAdjustment(),
                'percentageAdjustment' => $defaultRateSetting->getPercentageAdjustment(),
            ],
            'bitcoinRateSettings' => array_map(function($setting) {
                return [
                    'rangeFrom' => $setting->getRangeFrom(),
                    'rangeTo' => $setting->getRangeTo(),
                    'fixedAdjustment' => $setting->getFixedAdjustment(),
                    'percentageAdjustment' => $setting->getPercentageAdjustment(),
                ];
            }, $nonDefaultRateSettings),
        ];

        return $formData;
    }

    private function flattenFormErrors(Form $form)
    {
        $errors = [];

        foreach ($form->getErrors(true, true) as $error) {
            $fieldPath = '';

            $form = $error->getOrigin();
            while ($form->getParent() !== null) {
                // we're not yet at the root form since we have a parent
                $fieldPath = ((string) $form->getPropertyPath()).'.'.$fieldPath;
                $form = $form->getParent();
            }

            if ($fieldPath === '') {
                // in case the error is on the root form
                $fieldPath = 'form';
            }

            // remove trailing '.' if any
            $fieldPath = rtrim($fieldPath, '.');

            // 'field.[0]' => 'field[0]'
            $fieldPath = preg_replace('/\.\[/', '[', $fieldPath);

            $errors[$fieldPath][] = $error->getMessage();
        }

        return $errors;
    }

    public function getInvalidFormData(): array
    {
        $defaultRateSetting = (new BitcoinRateSetting)
            ->setRangeFrom(null)
            ->setRangeTo(null)
            ->setIsDefault(true)
            ->setFixedAdjustment('5.00')
        ;

        $nonDefaultRateSettings = [];

        $nonDefaultRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("0")
            ->setRangeTo("1.9999999999")
            ->setIsDefault(false)
        ;

        $nonDefaultRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("2")
            ->setRangeTo("2.9999999999")
            ->setIsDefault(false)
        ;

        return [
            [$defaultRateSetting, $nonDefaultRateSettings]
        ];
    }

    public function getValidFormData(): array
    {
        $defaultRateSetting = (new BitcoinRateSetting)
            ->setRangeFrom(null)
            ->setRangeTo(null)
            ->setIsDefault(true)
            ->setFixedAdjustment('5.00')
        ;

        $nonDefaultRateSettings = [];

        $nonDefaultRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("0")
            ->setRangeTo("1.9999999999")
            ->setIsDefault(false)
            ->setFixedAdjustment('10.00')
        ;

        $nonDefaultRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("2")
            ->setRangeTo("2.9999999999")
            ->setIsDefault(false)
            ->setPercentageAdjustment('5.00')
        ;

        return [
            [$defaultRateSetting, $nonDefaultRateSettings]
        ];
    }
}