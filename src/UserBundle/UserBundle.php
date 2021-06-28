<?php

namespace UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UserBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'user' => [
                'permission' => ['ROLE_USER_VIEW', 'ROLE_USER_CREATE'],
                'label' => $this->getTranslator()->trans('menus.User', [], 'UserBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-user',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_USER_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'UserBundle'),
                        'uri' => $this->getRouter()->generate('user.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_USER_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'UserBundle'),
                        'uri' => $this->getRouter()->generate('user.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_USER_VIEW' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.view',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_GROUP_VIEW'],
            ],
            'ROLE_USER_CREATE' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.create',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_USER_VIEW'],
            ],
            'ROLE_ADD_USER_ROLES' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.add_roles',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_USER_CREATE'],
            ],
            'ROLE_ADD_USER_GROUP' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.add_group',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_ADD_USER_ROLES'],
            ],
            'ROLE_USER_UPDATE' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.update',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_USER_VIEW'],
            ],
            'ROLE_CHANGE_USER_ROLES' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.change_roles',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_USER_UPDATE'],
            ],
            'ROLE_CHANGE_USER_GROUP' => [
                'group' => 'roles.groups.user',
                'label' => 'roles.user.change_group',
                'translation_domain' => 'UserBundle',
                'requirements' => ['ROLE_CHANGE_USER_ROLES'],
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
