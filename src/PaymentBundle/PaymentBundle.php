<?php

namespace PaymentBundle;

use DbBundle\Entity\Transaction;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PaymentBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'bitcoin.confirmations' => [],
        ];
    }
}
