<?php

namespace BrokerageBundle\Component;

use Psr\Http\Message\ResponseInterface;

class BrokerageApiComponent
{
    private $brokerage;

    public function __construct(BrokerageInterface $brokerage)
    {
        $this->brokerage = $brokerage;
    }

    protected function get(string $path, array $query = [], array $headers = []): ResponseInterface
    {
        return $this->brokerage->get($path, $query, $headers);
    }

    protected function post(string $path, array $postData = [], array $headers = []): ResponseInterface
    {
        return $this->brokerage->post($path, $postData, $headers);
    }

    protected function getBrokerage(): BrokerageInterface
    {
        return $this->brokerage;
    }
}
