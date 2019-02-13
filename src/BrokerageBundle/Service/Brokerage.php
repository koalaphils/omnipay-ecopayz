<?php

namespace BrokerageBundle\Service;

use BrokerageBundle\Component\BrokerageInterface;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\ResponseInterface;

class Brokerage implements BrokerageInterface
{
    private $brokerageUrl;
    private $accessToken;
    private $tokenType;
    private $client;

    private $membersComponent;

    public function __construct()
    {
        $this->client = new HttpMethodsClient(new GuzzleAdapter(new GuzzleClient()), new GuzzleMessageFactory());

        $this->membersComponent = new \BrokerageBundle\Component\BrokerageMembers($this);
    }

    public function get(string $path, array $query = [], array $headers = []): ResponseInterface
    {
        if (substr($path, '0', 8) === 'https://' || substr($path, '0', 7) === 'http://') {
            $endpoint = $path;
        } else {
            $endpoint = $this->brokerageUrl . $path;
        }

        $headers['Authorization'] = $this->tokenType . ' ' . $this->accessToken;

        return $this->client->get($endpoint . '?' . http_build_query($query), $headers);
    }

    public function post(string $path, array $postData = [], array $headers = []): ResponseInterface
    {
        if (substr($path, '0', 5) === 'https' || substr($path, '0', 4) === 'http') {
            $endpoint = $path;
        } else {
            $endpoint = $this->brokerageUrl . $path;
        }

        $headers['Authorization'] = $this->tokenType . ' ' . $this->accessToken;

        return $this->client->post($endpoint, $headers, http_build_query($postData));
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function setTokenType(string $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    public function setBrokerageUrl(string $brokerageUrl): void
    {
        $this->brokerageUrl = $brokerageUrl;
    }

    public function getMembersComponent(): \BrokerageBundle\Component\BrokerageMembers
    {
        return $this->membersComponent;
    }
}
