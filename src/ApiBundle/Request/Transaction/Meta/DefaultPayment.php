<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta;

class DefaultPayment implements PaymentInterface
{
    public static function createFromArray(array $data, string $transactionType): self
    {
        return new static();
    }

    public function toArray(): array
    {
        return [];
    }
}