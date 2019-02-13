<?php

namespace BrokerageBundle\Component;

use Psr\Http\Message\ResponseInterface;

interface BrokerageInterface
{
    public function get(string $path, array $query = [], array $headers = []): ResponseInterface;
    public function post(string $path, array $postData = [], array $headers = []): ResponseInterface;
    public function getMembersComponent(): BrokerageMembers;
}
