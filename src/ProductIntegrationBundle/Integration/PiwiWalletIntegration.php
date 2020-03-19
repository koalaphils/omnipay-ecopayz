<?php

namespace ProductIntegrationBundle\Integration;

// TODO: We can move Integrations to other folder so as to make this
// bundle reusable.

use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
Use DbBundle\Repository\CustomerProductRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\ValueObject\Number;

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
        $product = $this->repository->findByUsername($params[
            'id'
        ]);

        $sum = Number::add($product->getBalance(), $params['amount']);
        $product->setBalance($sum->toString());

        return $product->getBalance();
    }

    public function debit(string $token, array $params): string
    {
        $product = $this->repository->findByUsername($params[
            'id'
        ]);

        $diff = Number::sub($product->getBalance(), $params['amount']);
        $product->setBalance($diff->toString());

        return $product->getBalance();
    }
}
