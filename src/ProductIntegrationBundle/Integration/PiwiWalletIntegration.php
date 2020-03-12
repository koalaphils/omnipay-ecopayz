<?php

namespace ProductIntegrationBundle\Integration;

// TODO: We can move Integrations to other folder so as to make this
// bundle reusable.

use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
Use DbBundle\Repository\CustomerProductRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PiwiWalletIntegration implements ProductIntegrationInterface
{
    private $storage;
    private $repository;

    public function __construct(CustomerProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function auth(string $token, $body = []): array
    {
        return [];
    }

    public function getBalance(string $token, string $id): string
    {   
        // We will use $id as the Product's Username
        $product = $this->repository->findByUsername($id);

        return $product->getBalance();
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
