<?php

declare(strict_types = 1);

namespace PinnacleBundle\Service;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Product;
use DbBundle\Repository\ProductRepository;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PinnacleBundle\Component\AuthComponent;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use PinnacleBundle\Component\PinnacleInterface;
use PinnacleBundle\Component\PlayerComponent;
use PinnacleBundle\Component\ReportComponent;
use PinnacleBundle\Component\TokenGenerator;
use PinnacleBundle\Component\TransactionComponent;
use PinnacleBundle\Component\Wrapper\RequestWrapper;
use PinnacleBundle\Component\Wrapper\ResponseWrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class PinnacleService implements PinnacleInterface
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $agentCode;

    /**
     * @var string
     */
    private $agentKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var HttpMethodsClient
     */
    private $client;

    /**
     * @var GuzzleMessageFactory
     */
    private $requestMessageFactory;

    /**
     * @var AuthComponent
     */
    private $authComponent;

    /**
     * @var TransactionComponent
     */
    private $transactionComponent;

    /**
     * @var PlayerComponent
     */
    private $playerComponent;

     /**
     * @var ReportComponent
     */
    private $reportComponent;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(
        LoggerInterface $logger,
        SettingManager $settingManager,
        ProductRepository $productRepository,
        string $apiUrl,
        string $agentCode,
        string $agentKey,
        string $secretKey
    ) {
        $this->productRepository = $productRepository;
        $this->settingManager = $settingManager;
        $this->apiUrl = $apiUrl;
        $this->agentCode = $agentCode;
        $this->agentKey = $agentKey;
        $this->secretKey =  $secretKey;
        $this->logger = $logger;
        $this->requestMessageFactory = new GuzzleMessageFactory();
        $this->client = new HttpMethodsClient(new GuzzleAdapter(new GuzzleClient()), $this->requestMessageFactory);

        $this->tokenGenerator = new TokenGenerator($agentCode, $agentKey, $secretKey);
        $this->authComponent = new AuthComponent($this);
        $this->transactionComponent = new TransactionComponent($this);
        $this->playerComponent = new PlayerComponent($this);
        $this->reportComponent = new ReportComponent($this);
    }

    public function get(string $path, array $query = [], array $params = []): ResponseInterface
    {
        try {
            if (substr($path, 0, 8) === 'https://' || substr($path, 0, 7) === 'http://') {
                $endpoint = $path;
            } else {
                $endpoint = $this->apiUrl . $path;
            }

            $headers = $params['headers'] ?? [];
            $headers['Content-type'] = 'application/json';
            $headers['userCode'] = $this->agentCode;
            $headers['token'] = $headers['token'] ?? $this->generateToken();

            $request = $this->requestMessageFactory->createRequest('GET', $endpoint . '?' . urldecode(http_build_query($query)), $headers);
            $response = $this->client->sendRequest($request);

            $this->logger->debug('PIN API:'. __CLASS__ . ':' . __METHOD__, ['REQUEST' => new RequestWrapper($request), 'RESPONSE' => new ResponseWrapper($response)]);

            return $response;
        } catch (HttpException $ex) {
            $this->logger->critical($ex->getMessage(), ['REQUEST' => new RequestWrapper($ex->getRequest()), 'RESPONSE' => new ResponseWrapper($ex->getResponse())]);

            throw new PinnacleException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function post(string $path, array $postData = [], array $params = []): ResponseInterface
    {
        try {
            if (substr($path, '0', 5) === 'https' || substr($path, '0', 4) === 'http') {
                $endpoint = $path;
            } else {
                $endpoint = $this->apiUrl . $path;
            }

            $headers = $params['headers'] ?? [];
            $headers['Content-Type'] = 'application/json';
            $headers['userCode'] = $this->agentCode;
            $headers['token'] = $headers['token'] ?? $this->generateToken();

            $request = $this->requestMessageFactory->createRequest('POST', $endpoint, $headers, urldecode(http_build_query($postData)));
            $response = $this->client->sendRequest($request);

            $this->logger->debug('PIN API:'. __CLASS__ . ':' . __METHOD__, ['REQUEST' => new RequestWrapper($request), 'RESPONSE' => new ResponseWrapper($response)]);
       } catch (HttpException $ex) {
            $this->logger->critical($ex->getMessage(), ['REQUEST' => new RequestWrapper($ex->getRequest()), 'RESPONSE' => new ResponseWrapper($ex->getResponse())]);

            throw new PinnacleException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $response;
    }

    public function generateToken(): string
    {
        return $this->tokenGenerator->generate();
    }

    public function getAuthComponent(): AuthComponent
    {
        return $this->authComponent;
    }

    public function getTransactionComponent(): TransactionComponent
    {
        return $this->transactionComponent;
    }

    public function getPlayerComponent(): PlayerComponent
    {
        return $this->playerComponent;
    }

    public function getReportComponent(): ReportComponent
    {
        return $this->reportComponent;
    }


    public function getPinnacleProduct(): Product
    {
        $pinnacleProductCode = $this->settingManager->getSetting('pinnacle.product', '');

        return $this->productRepository->getProductByCode($pinnacleProductCode);
    }
}
