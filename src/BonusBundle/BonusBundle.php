<?php

namespace BonusBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BonusBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'bonus' => [
                'permission' => ['ROLE_BONUS_VIEW', 'ROLE_BONUS_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Bonus', [], 'BonusBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-gift',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_BONUS_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('bonus.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_BONUS_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('bonus.create_page'),
                    ],
                    'requested' => [
                        'permission' => ['ROLE_BONUS_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Requested List', [], 'BonusBundle'),
                        'uri' => $this->getRouter()->generate('bonus.requested_list_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_BONUS_VIEW' => [
                'group' => 'roles.groups.bonus',
                'label' => 'roles.bonus.view',
                'translation_domain' => 'BonusBundle',
            ],
            'ROLE_BONUS_CREATE' => [
                'group' => 'roles.groups.bonus',
                'label' => 'roles.bonus.create',
                'translation_domain' => 'BonusBundle',
            ],
            'ROLE_BONUS_UPDATE' => [
                'group' => 'roles.groups.bonus',
                'label' => 'roles.bonus.update',
                'translation_domain' => 'BonusBundle',
            ],
            'ROLE_BONUS_DELETE' => [
                'group' => 'roles.groups.bonus',
                'label' => 'roles.bonus.delete',
                'translation_domain' => 'BonusBundle',
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
