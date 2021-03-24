<?php

namespace PaymentBundle\Component\Blockchain;

use Codeception\Test\Unit;

class BicoinConverterTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider convertToBtcDataProvider
     */
    public function testConvertToBtc(string $sathosi, string $expectedResult)
    {
        $btc = BitcoinConverter::convertToBtc($sathosi);
        $this->assertSame($expectedResult, $btc);
    }

    /**
     * @dataProvider convertToSatoshiDataProvider
     */
    public function testConvertToSatoshi(string $btc, string $expectedResult)
    {
        $satoshi = BitcoinConverter::convertToSatoshi($btc);
        $this->assertSame($expectedResult, $satoshi);
    }

    public function convertToBtcDataProvider()
    {
        yield ['17500000000', '175.00000000000000000000'];
        yield ['2300000', '0.02300000000000000000'];
    }

    public function convertToSatoshiDataProvider()
    {
        yield ['175', '17500000000'];
        yield ['0.023', '2300000'];
    }
}
