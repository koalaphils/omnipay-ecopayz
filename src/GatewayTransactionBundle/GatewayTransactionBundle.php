<?php

namespace GatewayTransactionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GatewayTransactionBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'transaction' => [
                'permission' => ['ROLE_GATEWAY_TRANSACTION_VIEW', 'ROLE_GATEWAY_TRANSACTION_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Transaction', [], 'GatewayTransactionBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-exchange-vertical',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_GATEWAY_TRANSACTION_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.History', [], 'GatewayTransactionBundle'),
                        'uri' => $this->getRouter()->generate('gateway_transaction.list_page'),
                    ],
                    'deposit' => [
                        'permission' => ['ROLE_GATEWAY_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Deposit', [], 'GatewayTransactionBundle'),
                        'uri' => $this->getRouter()->generate('gateway_transaction.create_page', ['type' => 'deposit']),
                    ],
                    'withdraw' => [
                        'permission' => ['ROLE_GATEWAY_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Withdraw', [], 'GatewayTransactionBundle'),
                        'uri' => $this->getRouter()->generate('gateway_transaction.create_page', ['type' => 'withdraw']),
                    ],
                    'transfer' => [
                        'permission' => ['ROLE_GATEWAY_TRANSACTION_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Transfer', [], 'GatewayTransactionBundle'),
                        'uri' => $this->getRouter()->generate('gateway_transaction.create_page', ['type' => 'transfer']),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_GATEWAY_TRANSACTION_VIEW' => [
                'group' => 'roles.groups.gatewayTransaction',
                'label' => 'roles.gatewayTransaction.view',
                'translation_domain' => 'GatewayTransactionBundle',
            ],
            'ROLE_GATEWAY_TRANSACTION_CREATE' => [
                'group' => 'roles.groups.gatewayTransaction',
                'label' => 'roles.gatewayTransaction.create',
                'translation_domain' => 'GatewayTransactionBundle',
                'requirements' => ['ROLE_GATEWAY_TRANSACTION_VIEW'],
            ],
            'ROLE_GATEWAY_TRANSACTION_UPDATE' => [
                'group' => 'roles.groups.gatewayTransaction',
                'label' => 'roles.gatewayTransaction.update',
                'translation_domain' => 'GatewayTransactionBundle',
                'requirements' => ['ROLE_GATEWAY_TRANSACTION_VIEW'],
            ],
            'ROLE_GATEWAY_LOG_VIEW' => [
                'group' => 'roles.groups.gatewayTransaction',
                'label' => 'roles.gatewayTransaction.logView',
                'translation_domain' => 'GatewayTransactionBundle',
            ],
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/update.html.twig',
            'Default/create.html.twig',
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
