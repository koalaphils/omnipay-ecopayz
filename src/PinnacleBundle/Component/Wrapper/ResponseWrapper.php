<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Wrapper;

use Psr\Http\Message\ResponseInterface;

class ResponseWrapper implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function toArray(): array
    {
        $body = $this->response->getBody()->getContents();
        $this->response->getBody()->rewind();

        return [
            'reasonPhrase' => $this->response->getReasonPhrase(),
            'statusCode' => $this->response->getStatusCode(),
            'headers' => $this->response->getHeaders(),
            'protocol' => $this->response->getProtocolVersion(),
            'body' => $body,
        ];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}