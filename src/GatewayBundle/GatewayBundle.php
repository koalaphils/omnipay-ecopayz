<?php

namespace GatewayBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GatewayBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'gateway' => [
                'permission' => ['ROLE_GATEWAY_VIEW', 'ROLE_GATEWAY_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Gateway', [], 'GatewayBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-credit-card',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_GATEWAY_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('gateway.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_GATEWAY_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('gateway.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_GATEWAY_VIEW' => [
                'group' => 'roles.groups.gateway',
                'label' => 'roles.gateway.view',
                'translation_domain' => 'GatewayBundle',
                'requirements' => ['ROLE_CURRENCY_VIEW', 'ROLE_PAYMENTOPTION_VIEW']
            ],
            'ROLE_GATEWAY_CREATE' => [
                'group' => 'roles.groups.gateway',
                'label' => 'roles.gateway.create',
                'translation_domain' => 'GatewayBundle',
                'requirements' => ['ROLE_GATEWAY_VIEW'],
            ],
            'ROLE_GATEWAY_UPDATE' => [
                'group' => 'roles.groups.gateway',
                'label' => 'roles.gateway.update',
                'translation_domain' => 'GatewayBundle',
                'requirements' => ['ROLE_GATEWAY_VIEW'],
            ],
            'ROLE_CONFIGURE_GATEWAY' => [
                'group' => 'roles.groups.gateway',
                'label' => 'roles.gateway.configure',
                'translation_domain' => 'GatewayBundle',
                'requirements' => ['ROLE_GATEWAY_VIEW'],
            ],
            'ROLE_GATEWAY_CHANGE_STATUS' => [
                'group' => 'roles.groups.gateway',
                'label' => 'roles.gateway.change_status',
                'translation_domain' => 'GatewayBundle',
                'requirements' => ['ROLE_GATEWAY_VIEW'],
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
