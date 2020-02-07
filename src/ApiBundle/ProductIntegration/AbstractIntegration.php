<?php

namespace ApiBundle\ProductIntegration;

use GuzzleHttp\Client;

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

    protected function get(string $url, string $token, $data = []) 
    {

    }

    protected function post(string $url, string $token, $body = [])
    {
        return $this->client->post('/auth', [
            'json' => $body,
            'headers' => [
                'Authorization' => 'Bearer ' , $token,
            ]
        ]);
    }

    abstract public function auth(string $token): array;

    // Implements other necessary methods e.g credit, debit
}