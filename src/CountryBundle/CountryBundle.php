<?php

namespace CountryBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CountryBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'country' => [
                'permission' => ['ROLE_COUNTRY_VIEW', 'ROLE_COUNTRY_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Country', [], 'CountryBundle'),
                'uri' => $this->getRouter()->generate('country.list_page'),
                'icon' => 'ti-flag-alt-2',
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_COUNTRY_VIEW' => [
                'group' => 'roles.groups.country',
                'label' => 'roles.country.view',
                'translation_domain' => 'CountryBundle',
            ],
            'ROLE_COUNTRY_CREATE' => [
                'group' => 'roles.groups.country',
                'label' => 'roles.country.create',
                'translation_domain' => 'CountryBundle',
                'requirements' => ['ROLE_COUNTRY_VIEW', 'ROLE_CURRENCY_VIEW'],
            ],
            'ROLE_COUNTRY_UPDATE' => [
                'group' => 'roles.groups.country',
                'label' => 'roles.country.update',
                'translation_domain' => 'CountryBundle',
                'requirements' => ['ROLE_COUNTRY_VIEW', 'ROLE_CURRENCY_VIEW'],
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
