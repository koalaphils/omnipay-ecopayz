<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PaymentBundle\Manager;

use Codeception\Test\Unit;
use Doctrine\Common\Persistence\ObjectManager;
use DbBundle\Entity\BitcoinRateSetting;
use PaymentBundle\Component\Bitcoin\BitcoinAdjustmentInterface;
use PaymentBundle\Component\Blockchain\BlockChainInterface;
use PaymentBundle\Component\Model\BitcoinAdjustment;
use PaymentBundle\Service\Blockchain;
use UnitTester;
use AppBundle\Manager\SettingManager;

class BitcoinManagerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;
    
    public function testCreationOfBitcoinAdjustment()
    {
        $bitcoinManager = $this->createBitcoinManagerMock();
        $bitcoinAdjustment = $bitcoinManager->createBitcoinAdjustment('EUR');

        $this->assertInstanceOf(BitcoinAdjustment::class, $bitcoinAdjustment);
    }

    /**
     * @dataProvider bitcoinRateSettingsProvider
     */
    public function testBitcoinAdjustmentModel($bitcoinRateSettings)
    {
        $bitcoinAdjustment = $this->construct(BitcoinAdjustment::class, ['bitconRateSettings' => $bitcoinRateSettings]);
        $bitcoinAdjustment->setLatestBaseRate('6550.00');

        $result = $bitcoinAdjustment->createWebsocketPayload();
        $payload = json_decode($result, true);

        $this->assertArrayHasKey('latest_base_rate', $payload);
        $this->assertArrayHasKey('adjusted_base_rate', $payload);
        $this->assertArrayHasKey('conversion_table', $payload);
        $this->assertEquals($payload['latest_base_rate'], '6550.00');
        $this->assertEquals(count($payload['conversion_table']), 3);
        $this->assertEquals($payload['adjusted_base_rate'], '6560.00');
    }

    protected function createBitcoinManagerMock()
    {
        $bitcoinAdjustmentComponent = $this->getMockBuilder(BitcoinAdjustmentInterface::class)
            ->setMethods([
                'getAdjustment'
            ])
            ->getMock()
        ;

        $bitcoinAdjustmentComponent->expects($this->once())
            ->method('getAdjustment');
        
        $blockChain = $this->construct(Blockchain::class);

        $bitcoinManager = $this->make(BitcoinManager::class);
        $bitcoinManager->setBlockchain($blockChain);
        $bitcoinManager->setBitcoinAdjustmentComponent($bitcoinAdjustmentComponent);

        return $bitcoinManager;
    }

    public function bitcoinRateSettingsProvider()
    {
        $bitcoinRateSettings = [];
        $bitcoinRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom(null)
            ->setRangeTo(null)
            ->setIsDefault(true)
            ->setFixedAdjustment('5.00')
        ;

        $bitcoinRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("0")
            ->setRangeTo("1.9999999999")
            ->setIsDefault(false)
            ->setFixedAdjustment('10.00')
        ;

        $bitcoinRateSettings[] = (new BitcoinRateSetting)
            ->setRangeFrom("2")
            ->setRangeTo("2.9999999999")
            ->setIsDefault(false)
            ->setPercentageAdjustment('5.00')
        ;

        return [[$bitcoinRateSettings]];
    }

    public function testGetBitcoinConfiguration()
    {
        $settingManager = $this->make(SettingManager::class, [
            'getSetting' => function () {
                return [
                    "autoDecline" => true,
                    "minutesInterval" => 20,
                    "minimumAllowedDeposit" => "0.0000000001",
                    "maximumAllowedDeposit" => "9",
                ];
            }
        ]);

        $bitcoinManager = new BitcoinManager($settingManager);
        $bitcoinConfiguration = $bitcoinManager->getBitcoinConfiguration();
        
        $this->assertArrayHasKey('autoDecline', $bitcoinConfiguration);
        $this->assertArrayHasKey('minutesInterval', $bitcoinConfiguration);
        $this->assertArrayHasKey('minimumAllowedDeposit', $bitcoinConfiguration);
        $this->assertArrayHasKey('maximumAllowedDeposit', $bitcoinConfiguration); 
    }
}
