<?php

namespace CommissionBundle;

use CommissionBundle\Service\CommissionService;
use DbBundle\Entity\Transaction;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CommissionBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'commission' => [
                'enable' => false,
                'period' => [
                    'frequency' => CommissionService::SCHEDULER_FREQUENCY_MONTHLY,
                    'every' => 1,
                    'day' => 1,
                ],
                'payout' => [
                    'days' => 5,
                    'time' => '00:00',
                ],
                'conditions' => [
                    [
                        'field' => 'active_member',
                        'value' => '>=5',
                    ],
                ],
            ],
            'transaction.type' => [
                'start' => ['commission' => Transaction::TRANSACTION_STATUS_END],
                'workflow' => [
                    'commission' => [
                        Transaction::TRANSACTION_STATUS_END => [
                            'actions' => [['label' => 'approve', 'status' => Transaction::TRANSACTION_STATUS_END]],
                        ]
                    ],
                ],
            ],
            'transaction.equations' => [
                'commission' => [
                    'totalAmount' => [
                        'equation' => 'x+y',
                        'variables' => ['x' => 'sum_products', 'y' => 'total_customer_fee'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_products'],
                    ],
                ]
            ],
        ];
    }
}
