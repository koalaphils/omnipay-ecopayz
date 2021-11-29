<?php

namespace ProductIntegrationBundle\Integration;

use AppBundle\ValueObject\Number;
use Exception;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;
use ProductIntegrationBundle\Persistence\HttpPersistence;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ServerException;

class EwlIntegration implements ProductIntegrationInterface
{
    private $http;
    private $logger;

    public function __construct(HttpPersistence $http, LoggerInterface $logger)
    {
        $this->http = $http;
        $this->logger = $logger;
    }

    public function auth(string $token, $body = []): array
    {
        return [];
    }

    public function getBalance(string $token, string $id): string
    {
        $url = sprintf('/wallet/balance/%s', $id);
        $response = $this->http->get($url, $token);
        $body = json_decode(((string)$response->getBody()));
        $balance = !is_null($body->availableToBet) ? $body->availableToBet : 'Unable to fetch balance';

        return $balance;
    }

    public function credit(string $token, array $params): string
    {
        try {
            $url = sprintf('/wallet/transfer');
            $params['accountId'] = $params['id'];
            unset($params['id']);

            $response = $this->http->post($url, $token, $params);
            $body = json_decode(((string)$response->getBody()));

            return $body->availableToBet;
        } catch (Exception $exception) {
            throw new CreditIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function debit(string $token, array $params): string
    {
        try {
            $url = sprintf('/wallet/transfer');
            $params['accountId'] = $params['id'];

            if ($params['amount'] instanceof Number) {
                $params['amount'] = $params['amount']->toFloat();
            }
            
            $params['amount'] = -1 * abs($params['amount']);
            unset($params['id']);
            $response = $this->http->post($url, $token, $params);
            $body = json_decode(((string)$response->getBody()));

            return $body->availableToBet;
        } catch (Exception $exception) {
            throw new DebitIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function updateStatus(string $token, string $productUsername, bool $active)
    {   
        $customerId = explode('_', $productUsername)[1];
        $url = sprintf('/accounts/status/%s', $customerId);
        $response = $this->http->put($url, $token, [ 'active' => $active ]);
    }
}