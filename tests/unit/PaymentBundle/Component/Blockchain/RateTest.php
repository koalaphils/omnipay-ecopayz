<?php

namespace PaymentBundle\Component\Blockchain;

use Codeception\Test\Unit;
use Exception;
use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Mock\Client;
use PaymentBundle\Component\Blockchain\Exceptions\CurrencyRateException;
use PaymentBundle\Service\Blockchain;
use Psr\Http\Message\ResponseInterface;
use UnitTester;
use function GuzzleHttp\Psr7\parse_response;

class RateTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testTicker(): void
    {
        $client = new Client();
        $client->addResponse($this->generateMockResponse('BlockchainTicker'));

        $blockchain = new Blockchain('', '');
        $blockchain->setClient(new HttpMethodsClient($client, MessageFactoryDiscovery::find()));

        $result = $blockchain->getRate()->ticker();

        $this->assertTrue(is_array($result), 'The result must be an array');
        $this->assertArrayHasKey('EUR', $result);
        foreach ($result as $currency => $data) {
            $this->assertArrayHasKey('last', $data);
        }
    }

    /**
     * @dataProvider fromBTCDataProvider
     */
    public function testFromBTC(string $currency, string $expectedResult): void
    {
        $client = new Client();
        $client->addResponse($this->generateMockResponse('BlockchainTicker'));

        $blockchain = new Blockchain('', '');
        $blockchain->setClient(new HttpMethodsClient($client, MessageFactoryDiscovery::find()));

        $result = $blockchain->getRate()->fromBTC($currency);

        $this->assertSame($expectedResult, $result);
    }

    public function fromBTCDataProvider()
    {
        yield ['EUR', '370.13'];
        yield ['USD', '478.68'];
        yield ['GBP', '297.4'];
    }

    public function testFromBTCWithException(): void
    {
        $client = new Client();
        $client->addResponse($this->generateMockResponse('BlockchainTicker'));

        $blockchain = new Blockchain('', '');
        $blockchain->setClient(new HttpMethodsClient($client, MessageFactoryDiscovery::find()));
        try {
            $result = $blockchain->getRate()->fromBTC('PHP');
        } catch (Exception $e) {
            $this->assertInstanceOf(CurrencyRateException::class, $e);
            $this->assertSame('Currency PHP was unable to convert from BTC', $e->getMessage());
        }
    }

    private function generateMockResponse(string $file): ResponseInterface
    {
        return parse_response(file_get_contents(dirname(dirname(__DIR__)) . "/Mock/" . $file . '.txt'));
    }
}
