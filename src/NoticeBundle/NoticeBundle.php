<?php

namespace NoticeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class NoticeBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'notice' => [
                'permission' => ['ROLE_NOTICE_VIEW', 'ROLE_NOTICE_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Notice', [], 'NoticeBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-alert',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_NOTICE_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('notice.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_NOTICE_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('notice.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_NOTICE_VIEW' => [
                'group' => 'roles.groups.notice',
                'label' => 'roles.notice.view',
                'translation_domain' => 'NoticeBundle',
            ],
            'ROLE_NOTICE_CREATE' => [
                'group' => 'roles.groups.notice',
                'label' => 'roles.notice.create',
                'translation_domain' => 'NoticeBundle',
            ],
            'ROLE_NOTICE_UPDATE' => [
                'group' => 'roles.groups.notice',
                'label' => 'roles.notice.update',
                'translation_domain' => 'NoticeBundle',
            ],
            'ROLE_NOTICE_DELETE' => [
                'group' => 'roles.groups.notice',
                'label' => 'roles.notice.delete',
                'translation_domain' => 'NoticeBundle',
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
