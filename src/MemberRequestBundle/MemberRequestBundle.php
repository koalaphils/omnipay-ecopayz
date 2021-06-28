<?php

namespace MemberRequestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\DataCollectorTranslator;

class MemberRequestBundle extends Bundle
{
    public function registerMenu(): array
    {
        return [
            'memberRequest' => [
                'permission' => ['ROLE_MEMBER_REQUEST_VIEW'],
                'label' => $this->getTranslator()->trans('menus.MemberRequest', [], 'MemberRequestBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'icon-handbag',
                'subMenus' => [
                    'pending' => [
                        'permission' => ['ROLE_MEMBER_REQUEST_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List.Pending', [], 'MemberRequestBundle'),
                        'uri' => $this->getRouter()->generate('member_request.pending'),
                    ],
                    'history' => [
                        'permission' => ['ROLE_MEMBER_REQUEST_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List.History', [], 'MemberRequestBundle'),
                        'uri' => $this->getRouter()->generate('member_request.history'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole(): array
    {
        return [
            'ROLE_MEMBER_REQUEST_VIEW' => [
                'group' => 'roles.groups.memberRequest',
                'label' => 'roles.memberRequest.view',
                'translation_domain' => 'MemberRequestBundle',
            ],
            'ROLE_MEMBER_REQUEST_CREATE' => [
                'group' => 'roles.groups.memberRequest',
                'label' => 'roles.memberRequest.create',
                'translation_domain' => 'MemberRequestBundle',
                'requirements' => ['ROLE_MEMBER_REQUEST_VIEW'],
            ],
            'ROLE_MEMBER_REQUEST_UPDATE' => [
                'group' => 'roles.groups.memberRequest',
                'label' => 'roles.memberRequest.update',
                'translation_domain' => 'MemberRequestBundle',
                'requirements' => ['ROLE_MEMBER_REQUEST_VIEW'],
            ],
            'ROLE_MEMBER_REQUEST_DELETE' => [
                'group' => 'roles.groups.memberRequest',
                'label' => 'roles.memberRequest.delete',
                'translation_domain' => 'MemberRequestBundle',
                'requirements' => ['ROLE_MEMBER_REQUEST_VIEW', 'ROLE_MEMBER_REQUEST_UPDATE']
            ],
        ];
    }

    public function registerThemetViews(): array
    {
        return [
            'MemberRequest/list.html.twig',
            'MemberRequest/type/productPassword.html.twig',
            'MemberRequest/type/gAuth.html.twig',
            'MemberRequest/type/kyc.html.twig',
        ];
    }

    protected function getRouter(): Router
    {
        return $this->container->get('router');
    }

    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
