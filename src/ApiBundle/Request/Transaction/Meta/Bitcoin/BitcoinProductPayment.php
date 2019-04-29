<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta\Bitcoin;

use ApiBundle\Request\Transaction\Meta\PaymentInterface;

class BitcoinProductPayment implements PaymentInterface
{
    private $bitcoin;

    public static function createFromArray(array $data, string $transactionType): self
    {
        $instance = new static();
        $instance->bitcoin = (string) ($data['requested_btc'] ?? '');

        return $instance;
    }

    public function getBitcoin(): string
    {
        return $this->bitcoin;
    }

    public function toArray(): array
    {
        return ['requested_btc' => $this->bitcoin];
    }
}