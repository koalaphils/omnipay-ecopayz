<?php

namespace ProductIntegrationBundle\Integration;

// TODO: We can move Integrations to other folder so as to make this
// bundle reusable.

use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
Use DbBundle\Repository\CustomerProductRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\ValueObject\Number;
use CustomerBundle\Manager\CustomerProductManager;
use Doctrine\ORM\EntityManagerInterface;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;

class PiwiWalletIntegration implements ProductIntegrationInterface
{
    private $repository;
    private $manager;

    public function __construct(CustomerProductRepository $repository,
        EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
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
        $this->save($product);

        return $product->getBalance();
    }

    public function debit(string $token, array $params): string
    {
        $product = $this->repository->findByUsername($params[
            'id'
        ]);

        $diff = Number::sub($product->getBalance(), $params['amount']);
        
        if ($diff->lessThan(0)) {
            throw new DebitIntegrationException('Insufficient balance', 422);    
        }
        
        $product->setBalance($diff->toString());
        $this->save($product);

        return $product->getBalance();
    }

    private function save($product): void   
    {   
        $this->manager->persist($product);
        $this->manager->flush();
    }
}
