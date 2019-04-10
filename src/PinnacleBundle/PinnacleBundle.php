<?php

namespace PinnacleBundle;

use DbBundle\Entity\Transaction;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PinnacleBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'pinnacle' => [
                'product' => '',
                'transaction' => [
                    'deposit' => ['status' => Transaction::TRANSACTION_STATUS_END],
                    'withdraw' => ['status' => Transaction::TRANSACTION_STATUS_END],
                ]
            ],
        ];
    }

    public function registerSettingCodes()
    {
        return ['pinnacle'];
    }
}
