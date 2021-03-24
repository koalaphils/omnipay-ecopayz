<?php

namespace ProductIntegrationBundle\Integration;

interface ProductIntegrationInterface
{
    public function auth(string $token, array $auth = []): array;
    public function getBalance(string $token, string $id): string;
    public function credit(string $token, array $params): string;
    public function debit(string $token, array $params): string;
}