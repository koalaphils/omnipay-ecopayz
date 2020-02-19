<?php

namespace ApiBundle\ProductIntegration;

class EvolutionIntegration extends AbstractIntegration
{
    public function __construct(string $url)
    {
        parent::__construct($url);
    }

    public function auth(string $token, $body = []): array
    {
        $response = $this->post('/auth', $token, $body);
        $object = json_decode(((string) $response->getBody()));
        
        return [
            'entry' => $object->entry,
            'entry_embedded' => $object->entryEmbedded
        ];
    }

    public function getBalance(string $token, string $id): string
    {
        $response = $this->get('/balance' . '?id=' . $id, $token);
        $object = json_decode(((string) $response->getBody()));
        
        return $object->userbalance->tbalance;
    }

    public function credit(string $token, array $params): string
    {
        $transactionId = 'Credit_' . uniqid();
        $url = sprintf('/credit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
        $response = $this->get($url, $token);
        $object = json_decode(((string) $response->getBody()));
        

        dump($object);

        return $object->transfer->balance;
    }

    public function debit(string $token, array $params): string
    {  
        $transactionId = 'Debit_' . uniqid();
        $url = sprintf('/debit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $transactionId);
        $response = $this->get($url, $token);
        $object = json_decode(((string) $response->getBody()));

        return $object->transfer->balance;
    }
}