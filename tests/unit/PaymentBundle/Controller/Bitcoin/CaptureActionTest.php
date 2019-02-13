<?php

namespace PaymentBundle\Controller\Bitcoin;

use Codeception\Test\Unit;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Transaction;
use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Mock\Client;
use PaymentBundle\Model\Payment;
use PaymentBundle\Service\Blockchain;
use Payum\Core\Model\Token;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactory;
use Payum\Core\Security\TokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use UnitTester;
use function GuzzleHttp\Psr7\parse_response;

class CaptureActionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testExecute()
    {
        $tokenFactory = $this->createTokenFactory();

        $captureAction = new CaptureAction();
        $captureAction->setTokenFactory($tokenFactory);
        $captureAction->setBlockchain($this->createBlockchain());

        $model = new Payment();
        $model->setTransaction($this->tester->make(Transaction::class));
        $model->setGateway($this->tester->make(Gateway::class, [
            'details' => ['config' => ['xpub' => '']],
            'paymentOptionEntity' => $this->tester->make(PaymentOption::class, [
                'paymentMode' => PaymentOption::PAYMENT_MODE_BITCOIN,
            ]),
        ]));

        $captureRequest = new Capture(new Token());
        $captureRequest->setModel($model);

        $captureAction->execute($captureRequest);
        $transaction = $model->getTransaction();

        $this->assertSame('16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv', $transaction->getBitcoinAddress());
        $this->assertSame('http://mock.com/callback', $transaction->getBitcoinCallback());
        $this->assertSame(23, $transaction->getBitcoinIndex());
    }

    private function createBlockchain(): Blockchain
    {
        $client = new Client();
        $client->addResponse($this->generateMockResponse('BlockchainGap'));
        $client->addResponse($this->generateMockResponse('BlockchainGenerateAddress'));
        $blockchain = new Blockchain('', '');
        $blockchain->setClient(new HttpMethodsClient($client, MessageFactoryDiscovery::find()));

        return $blockchain;
    }

    private function createTokenFactory(): GenericTokenFactory
    {
        $tokenNotify = $this->getMockBuilder(TokenInterface::class)->getMock();
        $tokenNotify->expects($this->once())->method('getTargetUrl')->willReturn('http://mock.com/callback');

        $token = $this
            ->getMockBuilder(TokenFactoryInterface::class)
            ->getMock();
        $token
            ->expects($this->once())
            ->method('createToken')
            ->willReturn($tokenNotify);

        return new GenericTokenFactory($token, ['notify' => 'http://mock.com/callback']);
    }

    private function generateMockResponse(string $file): ResponseInterface
    {
        return parse_response(file_get_contents(dirname(dirname(__DIR__)) . "/Mock/" . $file . '.txt'));
    }
}
