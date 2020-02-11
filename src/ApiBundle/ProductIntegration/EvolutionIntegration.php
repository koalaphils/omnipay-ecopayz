<?php

namespace ApiBundle\ProductIntegration;

use GuzzleHttp\Exception\ClientException;

class EvolutionIntegration extends AbstractIntegration
{
    public function __construct(string $url)
    {
        parent::__construct($url);
    }

    public function auth(string $token, $body = []): array
    {
        try {
            $response = $this->post('/auth', $token, $body);
            $object = json_decode(((string) $response->getBody()));
            
            return [
                'entry' => $object->entry,
                'entry_embedded' => $object->entryEmbedded
            ];
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse());
        }
    }

    public function getBalance(string $token, string $id): string
    {
        try {
            $response = $this->get('/balance' . '?id=' . $id, $token);
            $object = json_decode(((string) $response->getBody()));
            
            return $object->userbalance->tbalance;

        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse());
        }
    }

    public function credit(string $token, array $params): string
    {
        try {
            $url = sprintf('/credit?id=%s&amount=%s&transactionId=%s', $params['id'], $params['amount'], $params['transactionId']);
            $response = $this->get($url, $token);
            $object = json_decode(((string) $response->getBody()));
            dump($object);
            return 'MEOW';

        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse());
        }
    }
}