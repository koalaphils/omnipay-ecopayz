<?php

namespace ProductIntegrationBundle\Integration;

use ProductIntegrationBundle\Persistence\HttpPersistence;
use ProductIntegrationBundle\Exception\IntegrationException;

class EvolutionIntegration implements ProductIntegrationInterface
{
    private $http;

    public function __construct(HttpPersistence $http)
    {
        $this->http = $http;
    }

    public function auth(string $token, $body = []): array
    {
        $response = $this->http->post('/auth', $token, $body);
        $object = json_decode(((string) $response->getBody()));
        
        return [
            'entry' => $object->entry,
            'entry_embedded' => $object->entryEmbedded,
            'session_id' => $object->sessionId,
        ];
    }

    public function getBalance(string $token, string $id): string
    {
        $response = $this->http->get('/balance' . '?id=' . $id, $token);
        $object = json_decode(((string) $response->getBody()));

        return $object->userbalance->tbalance;
    }

    public function credit(string $token, array $params): string
    {
        $transactionId = 'Credit_' . uniqid();
        $url = sprintf('/credit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
        $response = $this->http->get($url, $token);
        $object = json_decode(((string) $response->getBody()));

        return $object->transfer->balance;
    }

    public function debit(string $token, array $params): string
    {  
        $transactionId = 'Debit_' . uniqid();
        $url = sprintf('/debit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
        $response = $this->http->get($url, $token);
        $object = json_decode(((string) $response->getBody()));

        if (property_exists($object->transfer, 'errormsg')) {
            $message = $object->transfer->errormsg;
            if (strrpos($object->transfer->errormsg, 'Insufficient') !== false) {
                $message = 'Insufficient balance';
            }
            
            throw new IntegrationException($message, 422);
        }

        return $object->transfer->balance;
    }
}