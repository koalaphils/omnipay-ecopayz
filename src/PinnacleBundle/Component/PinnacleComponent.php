<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use PinnacleBundle\Component\Exceptions\PinnacleError;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use Psr\Http\Message\ResponseInterface;

abstract class PinnacleComponent
{
    /**
     * @var PinnacleInterface
     */
    private $pinnacle;

    public function __construct(PinnacleInterface $pinnacle)
    {
        $this->pinnacle = $pinnacle;
    }

    protected function get(string $path, array $query = [], array $params = []): array
    {
        $response = $this->pinnacle->get($path, $query, $params)->getBody()->getContents();
        $data = json_decode($response, true);
        $this->checkResponse($data);

        return $data;
    }

    protected function post(string $path, array $postData = [], array $params = []): array
    {
        $data = json_decode($this->pinnacle->post($path, $postData, $params)->getBody()->getContents(), true);
        $this->checkResponse($data);

        return $data;
    }

    protected function getPinnacle(): PinnacleInterface
    {
        return $this->pinnacle;
    }

    protected function checkResponse(array $response): void
    {
        if (array_has($response, 'code') && array_has($response, 'message')) {
            throw new PinnacleError($response['message'], (int) $response['code']);
        } elseif (array_has($response, 'trace')) {
            throw new PinnacleException($response['trace']);
        }
    }
}
