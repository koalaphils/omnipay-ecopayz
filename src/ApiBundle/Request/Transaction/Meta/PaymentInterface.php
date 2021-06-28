<?php

namespace ApiBundle\Request\Transaction\Meta;

interface PaymentInterface
{
    public static function createFromArray(array $data, string $transactionType);

    public function toArray(): array;
}
