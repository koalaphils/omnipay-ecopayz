<?php

namespace TransactionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use DbBundle\Entity\Transaction;

class TransactionBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'transaction' => [
                'permission' => ['ROLE_TRANSACTION_VIEW', 'ROLE_TRANSACTION_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Transaction', [], 'TransactionBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-exchange-vertical',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_TRANSACTION_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.History', [], 'TransactionBundle'),
                        'uri' => $this->getRouter()->generate('transaction.list_page'),
                    ],
                    'deposit' => [
                        'permission' => ['ROLE_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Deposit', [], 'TransactionBundle'),
                        'uri' => $this->getRouter()->generate('transaction.create_page', ['type' => 'deposit']),
                    ],
                    'withdraw' => [
                        'permission' => ['ROLE_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Withdraw', [], 'TransactionBundle'),
                        'uri' => $this->getRouter()->generate('transaction.create_page', ['type' => 'withdraw']),
                    ],
                    'transfer' => [
                        'permission' => ['ROLE_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Transfer', [], 'TransactionBundle'),
                        'uri' => $this->getRouter()->generate('transaction.create_page', ['type' => 'transfer']),
                    ],
                    'p2pTransfer' => [
                        'permission' => ['ROLE_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.P2PTransfer', [], 'TransactionBundle'),
                        'uri' => $this->getRouter()->generate('transaction.create_page', ['type' => 'p2p_transfer']),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_TRANSACTION_VIEW' => [
                'group' => 'roles.groups.transaction',
                'label' => 'roles.transaction.view',
                'translation_domain' => 'TransactionBundle',
            ],
            'ROLE_TRANSACTION_CREATE' => [
                'group' => 'roles.groups.transaction',
                'label' => 'roles.transaction.create',
                'translation_domain' => 'TransactionBundle',
                'requirements' => [
                    'ROLE_TRANSACTION_VIEW',
                    'ROLE_CUSTOMER_VIEW',
                    'ROLE_CUSTOMER_PRODUCT_VIEW'
                ],
            ],
            'ROLE_TRANSACTION_UPDATE' => [
                'group' => 'roles.groups.transaction',
                'label' => 'roles.transaction.update',
                'translation_domain' => 'TransactionBundle',
                'requirements' => [
                    'ROLE_TRANSACTION_VIEW',
                    'ROLE_CUSTOMER_VIEW',
                    'ROLE_CUSTOMER_PRODUCT_VIEW'
                ],
            ],
            'ROLE_TRANSACTION_SETTING_PROCESS' => [
                'group' => 'roles.groups.transaction',
                'label' => 'roles.transaction.changeProcess',
                'translation_domain' => 'TransactionBundle',
                'requirements' => ['ROLE_UPDATE_SETTINGS'],
            ],
        ];
    }

    public function registerSettingMenu()
    {
        return [
            'transaction' => [
                'label' => 'settings.transaction.label::TransactionBundle',
                'description' => 'settings.transaction.description::TransactionBundle',
                'uri' => $this->getRouter()->generate('transaction.setting.transaction_page'),
                'permission' => ['ROLE_MAINTENANCE'],
            ],
        ];
    }

    public function registerDefaultSetting()
    {
        return [
            'transaction.status.1' => [
                'label' => 'Requested',
                'start' => true,
            ],
            'transaction.status.2' => [
                'label' => 'Approved',
                'end' => true,
            ],
            'transaction.status.3' => [
                'label' => 'Declined',
                'decline' => true,
            ],
            'transaction.statusCounter' => [
                Transaction::TRANSACTION_STATUS_START => ['conditions' => [], 'params' => []],
                Transaction::TRANSACTION_STATUS_END => [
                    'conditions' => ['transaction.updatedAt >= :from_2'],
                    'params' => [
                        'from_2' => 'date("Y-m-d 12:00:00P", (strtotime(date("Y-m-d H:i:sP")) > strtotime(date("Y-m-d 12:00:00P"))) ? strtotime(date("Y-m-d")) : strtotime(date("Y-m-d") ~ " -1 day"))',
                    ],
                ],
                Transaction::TRANSACTION_STATUS_DECLINE => [
                    'conditions' => ['transaction.updatedAt >= :from_3'],
                    'params' => [
                        'from_3' => 'date("Y-m-d 12:00:00P", (strtotime(date("Y-m-d H:i:sP")) > strtotime(date("Y-m-d 12:00:00P"))) ? strtotime(date("Y-m-d")) : strtotime(date("Y-m-d") ~ " -1 day"))',
                    ],
                ],
                'voided' => [
                    'conditions' => ['transaction.updatedAt >= :from_voided'],
                    'params' => [
                        'from_voided' => 'date("Y-m-d 12:00:00P", (strtotime(date("Y-m-d H:i:sP")) > strtotime(date("Y-m-d 12:00:00P"))) ? strtotime(date("Y-m-d")) : strtotime(date("Y-m-d") ~ " -1 day"))',
                    ],
                ],
            ],
            'transaction.start' => [
                'customer' => Transaction::TRANSACTION_STATUS_START,
                'admin' => Transaction::TRANSACTION_STATUS_START,
            ],
            'transaction.paymentGateway' => 'customer-group',
            'transaction.equations' => [
                'bonus' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_products'],
                    ]
                ],
                'deposit' => [
                    'totalAmount' => [
                        'equation' => 'x+y',
                        'variables' => ['x' => 'sum_products', 'y' => 'total_customer_fee'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_products'],
                    ],
                ],
                'withdraw' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x-y',
                        'variables' => ['x' => 'sum_products', 'y' => 'total_customer_fee'],
                    ],
                ],
                'transfer' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                ],
                'p2p_transfer' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                ],
                'debit_adjustment' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_withdraw_products'],
                    ],
                ],
                'credit_adjustment' => [
                    'totalAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_deposit_products'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x',
                        'variables' => ['x' => 'sum_deposit_products'],
                    ],
                ],
            ],
        ];
    }

    public function registerSettingCodes()
    {
        return [
            'transaction.status',
            'transaction.equations',
            'transaction.paymentGateway',
            'counter',
            'transaction.list.filters',
            'transaction.start',
            'transaction.type',
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/update.html.twig',
            'Default/create.html.twig',
            'Setting/index.html.twig',
            'Default/Type/bonus.html.twig',
            'Default/Type/deposit.html.twig',
            'Default/Type/transfer.html.twig',
            'Default/Type/withdraw.html.twig',
        ];
    }

    /**
     * Get router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
