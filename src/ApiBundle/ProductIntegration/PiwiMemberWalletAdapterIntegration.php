<?php

namespace ApiBundle\ProductIntegration\PiwiMemberWalletIntegration;

use DbBundle\Repository\CustomerProductRepository;

class PiwiMemberWalletAdapterIntegration extends AbstractIntegration
{
    public function __construct(CustomerProductRepository $customerProductRepository) 
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