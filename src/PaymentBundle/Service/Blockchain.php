<?php

namespace PaymentBundle\Service;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PaymentBundle\Component\Blockchain\BlockchainInterface;
use PaymentBundle\Component\Blockchain\Explorer;
use PaymentBundle\Component\Blockchain\Rate;
use PaymentBundle\Component\Blockchain\ReceivePayment;
use PaymentBundle\Component\Blockchain\Wallet\Wallet;
use PaymentBundle\Component\Blockchain\XPubScanner\XPubScanner;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Blockchain implements BlockchainInterface
{
    public const BLOCKCHAIN_URL = "https://blockchain.info";
    public const BLOCKCHAIN_API_URL = 'https://api.blockchain.info';

    private $apiKey;
    private $client;
    private $walletUrl;
    private $requestMessageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    // Components
    private $rate;
    private $receivePayment;
    private $explorer;
    private $wallet;
    private $xpubScanner;

    public function __construct(string $apiKey, string $walletUrl)
    {
        $this->requestMessageFactory = new GuzzleMessageFactory();
        // zimi-temporary disabled verify ssl
        $this->client = new HttpMethodsClient(new GuzzleAdapter(new GuzzleClient(['curl'=>[CURLOPT_SSL_VERIFYPEER => 0]])), $this->requestMessageFactory);

        $this->apiKey = $apiKey;

        $this->rate = new Rate($this);
        $this->receivePayment = new ReceivePayment($this);
        $this->explorer = new Explorer($this);
        $this->wallet = new Wallet($this, $walletUrl);
    }

    public function setClient(HttpMethodsClient $client): void
    {
        $this->client = $client;
    }

    public function getRate(): Rate
    {
        return $this->rate;
    }

    public function getReceivePayment(): ReceivePayment
    {
        return $this->receivePayment;
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function setXPubScanner(XPubScanner $xpubScanner): void
    {
        $this->xpubScanner = $xpubScanner;
    }

    public function getXubScanner(): ?XPubScanner
    {
        return $this->xpubScanner;
    }

    public function get(string $path, array $query = [], array $headers = []): ResponseInterface
    {
        if (substr($path, '0', 8) === 'https://' || substr($path, '0', 7) === 'http://') {
            $endpoint = $path;
        } else {
            $endpoint = self::BLOCKCHAIN_URL . $path;
        }

        $request = $this->requestMessageFactory->createRequest('GET', $endpoint . '?' . urldecode(http_build_query($query)), $headers);
        $this->logRequest($request);
        $response = $this->client->sendRequest($request);
        $this->logResponse($response);

        return $response;
    }

    public function post(string $path, array $postData = [], array $headers = []): ResponseInterface
    {
        if (substr($path, '0', 5) === 'https' || substr($path, '0', 4) === 'http') {
            $endpoint = $path;
        } else {
            $endpoint = self::BLOCKCHAIN_URL . $path;
        }

        $request = $this->requestMessageFactory->createRequest('POST', $endpoint, $headers, urldecode(http_build_query($postData)));
        $this->logRequest($request);
        $response = $this->client->sendRequest($request);
        $this->logResponse($response);

        return $response;
    }

    public function apiGet(string $path, array $query = [], array $headers = []): ResponseInterface
    {
        return $this->get(self::BLOCKCHAIN_API_URL . $path, $query, $headers);
    }

    public function apiPost(string $path, array $postData = [], array $params = []): ResponseInterface
    {
        return $this->post(self::BLOCKCHAIN_API_URL . $path, $postData, $params);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function logRequest(RequestInterface $request): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('BO to Blockchain Request', [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
                'method' => $request->getMethod(),
                'protocolVersion' => $request->getProtocolVersion(),
                'requestTarget' => $request->getRequestTarget(),
                'uri' => $request->getUri(),
            ]);
        }
    }

    private function logResponse(ResponseInterface $response): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Blockchain Response', [
                'headers' => $response->getHeaders(),
                'protocolVersion' => $response->getProtocolVersion(),
                'statusCode' => $response->getStatusCode(),
                'reasonPhrase' => $response->getReasonPhrase(),
                'body' => $response->getBody(),
            ]);
        }
    }
}
