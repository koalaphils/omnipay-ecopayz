<?php

namespace ApiBundle\Request\Transaction\Meta;

interface PaymentInterface
{
    public static function createFromArray(array $data);

    public function toArray(): array;
}
