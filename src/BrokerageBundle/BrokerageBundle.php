<?php

namespace BrokerageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BrokerageBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'transaction.equations.bet' => [
                'totalAmount' => [
                    'equation' => 'x',
                    'variables' => ['x' => 'sum_products'],
                ],
                'customerAmount' => [
                    'equation' => 'x',
                    'variables' => ['x' => 'sum_products'],
                ],
            ],
        ];
    }
}
