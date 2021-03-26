<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use Psr\Http\Message\ResponseInterface;

interface PinnacleInterface
{
    public function get(string $path, array $query = [], array $params = []): ResponseInterface;

    public function post(string $path, array $postData = [], array $params = []): ResponseInterface;

    public function generateToken(): string;
}
