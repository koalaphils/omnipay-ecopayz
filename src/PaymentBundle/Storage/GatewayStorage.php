<?php

namespace PaymentBundle\Storage;

use DbBundle\Repository\GatewayRepository;
use Payum\Core\Storage\StorageInterface;

class GatewayStorage implements StorageInterface
{
    private $gatewayRepository;

    public function __construct(GatewayRepository $gatewayRepository)
    {
        $this->gatewayRepository = $gatewayRepository;
    }

    public function create()
    {
    }

    public function delete($model): void
    {
    }

    public function find($id)
    {
    }

    public function findBy(array $criteria): array
    {
        $gatewayName = $criteria['gatewayName'];
        $filters = [
            'paymentOption' => strtoupper($gatewayName),
        ];

        $result = $this->gatewayRepository->getGatewayList($filters, [], \Doctrine\ORM\Query::HYDRATE_OBJECT);

        return $result;
    }

    public function identify($model): \Payum\Core\Storage\IdentityInterface
    {
    }

    public function support($model): bool
    {
    }

    public function update($model): void
    {
    }
}
