<?php

namespace CurrencyBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CurrencyBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'currency' => [
                'permission' => ['ROLE_CURRENCY_VIEW', 'ROLE_CURRENCY_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Currency', [], 'CurrencyBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-money',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_CURRENCY_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('currency.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_CURRENCY_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('currency.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_CURRENCY_VIEW' => [
                'group' => 'roles.groups.currency',
                'label' => 'roles.currency.view',
                'translation_domain' => 'CurrencyBundle',
            ],
            'ROLE_CURRENCY_CREATE' => [
                'group' => 'roles.groups.currency',
                'label' => 'roles.currency.create',
                'translation_domain' => 'CurrencyBundle',
                'requirements' => ['ROLE_CURRENCY_VIEW'],
            ],
            'ROLE_CURRENCY_UPDATE' => [
                'group' => 'roles.groups.currency',
                'label' => 'roles.currency.update',
                'translation_domain' => 'CurrencyBundle',
                'requirements' => ['ROLE_CURRENCY_VIEW'],
            ],
            'ROLE_CURRENCY_CHANGE_BASE' => [
                'group' => 'roles.groups.currency',
                'label' => 'roles.currency.changebase',
                'translation_domain' => 'CurrencyBundle',
                'requirements' => ['ROLE_UPDATE_SETTINGS'],
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

    public function registerSettingMenu()
    {
        return [
            'currency' => [
                'label' => 'settings.currency.label::CurrencyBundle',
                'description' => 'settings.transaction.description::CurrencyBundle',
                'uri' => $this->getRouter()->generate('currency.setting.currency_page'),
                'permission' => ['ROLE_CHANGE_BASE_CURRENCY'],
            ],
        ];
    }

    public function registerDefaultSetting()
    {
        return [
            'currency.base' => null,
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
