<?php

namespace ApiBundle\ProductIntegration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

// TODO: Create HTTPService

abstract class AbstractIntegration
{
    protected $url;
    protected $client;

    public function __construct(string $url) 
    {
        $this->url = $url;
        $this->client = new Client([
            'base_uri' => $this->url,
        ]);
    }

    protected function get(string $url, string $token) 
    {
        try {
            return $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' , $token,
                ]
            ]);
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (ConnectException  $e) {
            throw new IntegrationNotAvailableException($this->url);
        }
    }

    protected function post(string $url, string $token, $body = [])
    {
        try {
            return $this->client->post($url, [
                'json' => $body,
                'headers' => [
                    'Authorization' => 'Bearer ' , $token,
                ]
            ]);
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (ConnectException  $e) {
            throw new IntegrationNotAvailableException($this->url);
        }
    }

    abstract public function auth(string $token, array $auth = []): array;
    abstract public function getBalance(string $token, string $id): string;
    abstract public function credit(string $token, array $params): string;
    abstract public function debit(string $token, array $params): string;
}