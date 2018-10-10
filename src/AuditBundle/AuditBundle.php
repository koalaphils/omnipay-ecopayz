<?php

namespace AuditBundle;

use DbBundle\Entity\AuditRevision;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AuditBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'audit' => [
                'permission' => ['ROLE_AUDIT_VIEW'],
                'label' => $this->getTranslator()->trans('menus.Audit', [], 'AuditBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-alarm-clock',
                'subMenus' => [
                    'member' => [
                        'permission' => ['ROLE_AUDIT_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.Member', [], 'AuditBundle'),
                        'uri' => $this->getRouter()->generate('audit.list_page', ['type' => AuditRevision::TYPE_MEMBER]),
                    ],
                    'support' => [
                        'permission' => ['ROLE_AUDIT_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.Support', [], 'AuditBundle'),
                        'uri' => $this->getRouter()->generate('audit.list_page', ['type' => AuditRevision::TYPE_SUPPORT]),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_AUDIT_VIEW' => [
                'group' => 'roles.groups.audit',
                'label' => 'roles.audit.view',
                'translation_domain' => 'AuditBundle',
            ],
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
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
