<?php

namespace ProductIntegrationBundle\Integration;

use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;

class PiwiWalletIntegration implements ProductIntegrationInterface
{
    public function __construct()
    {
    }

    public function auth(string $token, $body = []): array
    {
        return [];
    }

    public function getBalance(string $token, string $id): string
    {
        return '0.00';
    }

    public function credit(string $token, array $params): string
    {
        return '0.00';
    }

    public function debit(string $token, array $params): string
    {
        return '0.00';
    }
}
