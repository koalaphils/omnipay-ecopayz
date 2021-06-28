<?php

namespace TicketBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TicketBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'ticket' => [
                'permission' => ['ROLE_TICKET_VIEW', 'ROLE_TICKET_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Ticket', [], 'TicketBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-user',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_TICKET_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'AppBundle'),
                        'uri' => $this->getRouter()->generate('ticket.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_TICKET_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'TicketBundle'),
                        'uri' => $this->getRouter()->generate('ticket.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_TICKET_VIEW' => [
                'group' => 'roles.groups.ticket',
                'label' => 'roles.ticket.view',
                'translation_domain' => 'TicketBundle',
            ],
            'ROLE_TICKET_CREATE' => [
                'group' => 'roles.groups.ticket',
                'label' => 'roles.ticket.create',
                'translation_domain' => 'TicketBundle',
            ],
            'ROLE_TICKET_UPDATE' => [
                'group' => 'roles.groups.ticket',
                'label' => 'roles.ticket.update',
                'translation_domain' => 'TicketBundle',
            ],
            'ROLE_TICKET_REPLY' => [
                'group' => 'roles.groups.ticket',
                'label' => 'roles.ticket.reply',
                'translation_domain' => 'TicketBundle',
            ],
            'ROLE_TICKET_DELETE' => [
                'group' => 'roles.groups.ticket',
                'label' => 'roles.ticket.delete',
                'translation_domain' => 'TicketBundle',
            ],
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/reply.html.twig',
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
