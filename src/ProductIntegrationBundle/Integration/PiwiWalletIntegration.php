<?php

namespace ProductIntegrationBundle\Integration;

// TODO: We can move Integrations to other folder so as to make this
// bundle reusable.

use AppBundle\ValueObject\Number;
use DbBundle\Repository\CustomerProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;
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
        try {
            $product = $this->repository->findByUsername($params['id']);

            $sum = Number::add($product->getBalance(), $params['amount']);
            $product->setBalance($sum->toString());
            $this->save($product);

            return $product->getBalance();
        } catch (Exception $exception) {
            throw new CreditIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function save($product): void
    {
        $this->manager->persist($product);
        $this->manager->flush();
    }

    public function debit(string $token, array $params): string
    {
        try {
            $product = $this->repository->findByUsername($params['id']);

            $diff = Number::sub($product->getBalance(), $params['amount']);

            if ($diff->lessThan(0)) {
                throw new DebitIntegrationException('Insufficient balance', 422);
            }

            $product->setBalance($diff->toString());
            $this->save($product);

            return $product->getBalance();
        } catch (Exception $exception) {
            throw new DebitIntegrationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function updateStatus(string $token, string $customerId, bool $active)
    {
        // NOOP
    }
}
