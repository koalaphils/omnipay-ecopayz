<?php

namespace GroupBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GroupBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'group' => [
                'permission' => ['ROLE_GROUP_VIEW', 'ROLE_GROUP_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Group', [], 'GroupBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'icon-people',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_GROUP_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'GroupBundle'),
                        'uri' => $this->getRouter()->generate('group.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_GROUP_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'GroupBundle'),
                        'uri' => $this->getRouter()->generate('group.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_GROUP_VIEW' => [
                'group' => 'roles.groups.group',
                'label' => 'roles.group.view',
                'translation_domain' => 'GroupBundle',
            ],
            'ROLE_GROUP_CREATE' => [
                'group' => 'roles.groups.group',
                'label' => 'roles.group.create',
                'translation_domain' => 'GroupBundle',
                'requirements' => ['ROLE_GROUP_VIEW']
            ],
            'ROLE_GROUP_UPDATE' => [
                'group' => 'roles.groups.group',
                'label' => 'roles.group.update',
                'translation_domain' => 'GroupBundle',
                'requirements' => ['ROLE_GROUP_VIEW']
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
