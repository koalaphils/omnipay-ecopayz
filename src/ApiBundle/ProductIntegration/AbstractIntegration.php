<?php

namespace ApiBundle\ProductIntegration;

use GuzzleHttp\Client;

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
        return $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' , $token,
            ]
        ]);
    }

    protected function post(string $url, string $token, $body = [])
    {
        return $this->client->post($url, [
            'json' => $body,
            'headers' => [
                'Authorization' => 'Bearer ' , $token,
            ]
        ]);
    }

    abstract public function auth(string $token, array $auth = []): array;
    abstract public function getBalance(string $token, string $id): string;

    // Implements other necessary methods e.g credit, debit
}