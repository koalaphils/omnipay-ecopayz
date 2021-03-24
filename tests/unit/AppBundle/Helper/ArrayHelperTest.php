<?php
namespace AppBundle\Helper;

class ArrayHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testAppendUsingKey()
    {
        $defaultArray = [];
        $updatedArray = ArrayHelper::append($defaultArray,'putito','putitoKey');

        $this->assertSame($updatedArray['putitoKey'], 'putito');
    }

    /**
     * @dataProvider appendDataProvider
     */
    public function testAppend($expectedCount, $defaultArray)
    {
        $updatedArray = ArrayHelper::append($defaultArray,'putito');

        $this->assertCount($expectedCount, $updatedArray);
    }

    /**
     * @return array
     */
    public function appendDataProvider()
    {
        return [
            [1, []],
            [2, ['masputito']],
            [3, ['masputito', 'putitochip']],
        ];
    }

    public function testGetWithoutKey()
    {
        $arrayUnderTest = [
            'A' => 'putito',
            'B' => 'masputito',
            'C' => 'putitochip'];

        $result = ArrayHelper::get($arrayUnderTest, null);
        $this->assertSame($arrayUnderTest, $result);
    }

    public function testGetUsingKey()
    {
        $arrayUnderTest = [
            'A' => 'putito',
            'B' => 'masputito',
            'C' => 'putitochip'];

        $result = ArrayHelper::get($arrayUnderTest, 'B');
        $this->assertSame('masputito', $result);
    }

    public function testGetUsingInvalidKey()
    {
        $arrayUnderTest = [
            'A' => 'putito',
            'B' => 'masputito',
            'C' => 'putitochip'];

        $result = ArrayHelper::get($arrayUnderTest, 'someUnknownKey');
        $this->assertSame(null, $result);
    }

    public function testGetUsingNestedKey()
    {
        $arrayUnderTest = [
            'A' => 'putito',
            'B' => 'masputito',
            'C' => 'putitochip',
            'D' => [
                'A' => 'pusit',
                'B' => 'opusit'
            ]
        ];

        $result = ArrayHelper::get($arrayUnderTest, 'D.A');
        $this->assertSame('pusit', $result);
    }
}