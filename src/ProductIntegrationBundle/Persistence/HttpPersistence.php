<?php

namespace ProductIntegrationBundle\Persistence;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;

class HttpPersistence
{
    private $url;
    private $client;

    public function __construct(string $url) 
    {
        $this->url = $url;
        $this->client = new Client([
            'base_uri' => $this->url,
        ]);
    }

    public function get(string $url, string $token) 
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

    public function post(string $url, string $token, $body = [])
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
}