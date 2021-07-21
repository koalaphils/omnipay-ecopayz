<?php

namespace ProductIntegrationBundle\Integration;

use AppBundle\ValueObject\Number;
use Exception;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;
use ProductIntegrationBundle\Persistence\HttpPersistence;

class EwlIntegration implements ProductIntegrationInterface
{
    private $http;

    public function __construct(HttpPersistence $http)
    {
        $this->http = $http;
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

        return $body->availableToBet;
    }

    public function credit(string $token, array $params): string
    {
        try {
            $url = sprintf('/wallet/transfer');
            $params['accountId'] = $params['ids'];
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
            $params['accountId'] = $params['ids'];

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
}