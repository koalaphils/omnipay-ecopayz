<?php

namespace ProductIntegrationBundle\Integration;

use ProductIntegrationBundle\Persistence\HttpPersistence;
use ProductIntegrationBundle\Exception\IntegrationException;

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
        $body = json_decode(((string) $response->getBody()));

        return $body->availableToBet;
    }

    public function credit(string $token, array $params): string
    {
        $url = sprintf('/wallet/transfer');
        $params['accountId'] = $params['id'];
        unset($params['id']);

        $response = $this->http->post($url, $token, $params);
        $body = json_decode(((string) $response->getBody()));
      
        return $body->availableToBet;
    }

    public function debit(string $token, array $params): string
    {  
        $url = sprintf('/wallet/transfer');
        $params['accountId'] = $params['id'];
        $params['amount'] = -1 * abs($params['amount']);
        unset($params['id']);

        $response = $this->http->post($url, $token, $params);
        $body = json_decode(((string) $response->getBody()));
        
        return $body->availableToBet;
    }
}