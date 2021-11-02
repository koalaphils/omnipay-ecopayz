<?php

namespace ProductIntegrationBundle\Persistence;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;

class HttpPersistence
{
    private $baseUrl;
    private $client;

    public function __construct(string $url) 
    {
        $this->baseUrl = $url;
        $this->client = new Client([]);
    }

    public function get(string $url, string $token) 
    {
        try {
            return $this->client->get($this->baseUrl . $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (ConnectException  $e) {
            throw new IntegrationNotAvailableException($e->getMessage());
        }
    }

    public function post(string $url, string $token, $body = [])
    {
        try {
            return $this->client->post($this->baseUrl . $url, [
                'json' => $body,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (ConnectException  $e) {
            throw new IntegrationNotAvailableException($this->baseUrl);
        }
    }

    public function put(string $url, string $token, $body = [])
    {
        try {
            return $this->client->put($this->baseUrl . $url, [
                'json' => $body,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
        } catch (ClientException $e) {
            throw new IntegrationException($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (ConnectException  $e) {
            throw new IntegrationNotAvailableException($this->baseUrl);
        }
    }
}