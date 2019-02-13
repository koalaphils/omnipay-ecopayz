<?php

namespace PaymentBundle\Component\Blockchain;

use Psr\Http\Message\ResponseInterface;

interface BlockchainInterface
{
    public function get(string $path, array $query = [], array $params = []): ResponseInterface;

    public function post(string $path, array $postData = [], array $params = []): ResponseInterface;

    public function apiGet(string $path, array $query = [], array $params = []): ResponseInterface;

    public function apiPost(string $path, array $postData = [], array $params = []): ResponseInterface;

    public function getApiKey(): string;
}
