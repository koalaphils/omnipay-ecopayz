<?php

namespace ProductIntegrationBundle\Integration;

use Exception;
use Psr\Log\LoggerInterface;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;
use ProductIntegrationBundle\Persistence\HttpPersistence;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;

class EvolutionIntegration implements ProductIntegrationInterface
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

        if (property_exists($object, 'error')) {
            $this->logger->info('EVOLUTION ERROR: ' . (string) $response->getBody());
            throw new IntegrationException('Error getting balance in evolution.', 422);
        }

        return $object->userbalance->tbalance;
    }

    public function credit(string $token, array $params): string
    {
        try {
            $transactionId = 'Credit_' . uniqid();
            $url = sprintf('/credit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
            $response = $this->http->get($url, $token);
            $object = json_decode(((string) $response->getBody()));

            return $object->transfer->balance;
        } catch (Exception $exception) {
            throw new CreditIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function debit(string $token, array $params): string
    {  
        try {
            $transactionId = 'Debit_' . uniqid();
            $url = sprintf('/debit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
            $response = $this->http->get($url, $token);
            $object = json_decode(((string) $response->getBody()));

            if (property_exists($object->transfer, 'errormsg')) {
                $message = $object->transfer->errormsg;
                if (strrpos($object->transfer->errormsg, 'Insufficient') !== false) {
                    $message = 'Insufficient balance';
                }
                
                throw new DebitIntegrationException($message, 422);
            }
            return $object->transfer->balance;
        } catch (Exception $exception) {
            throw new DebitIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}