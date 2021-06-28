<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Wrapper;

use Psr\Http\Message\RequestInterface;

class RequestWrapper implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function toArray(): array
    {
        $body = $this->request->getBody()->getContents();
        $this->request->getBody()->rewind();

        return [
            'method' => $this->request->getMethod(),
            'requestTarget' => $this->request->getRequestTarget(),
            'url' => (string) $this->request->getUri(),
            'body' => $body,
            'headers' => $this->request->getHeaders(),
            'protocol' => $this->request->getProtocolVersion(),
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