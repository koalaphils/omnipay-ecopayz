<?php

namespace ReportBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReportBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'reportProduct' => [
                'permission' => ['ROLE_REPORT_PRODUCT_VIEW'],
                'label' => $this->getTranslator()->trans('menus.report.product', [], 'ReportBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'icon-handbag',
                #'subMenus' => [
                #    'list' => [
                #        'permission' => ['ROLE_REPORT_PRODUCT_VIEW'],
                #        'label' => $this->getTranslator()->trans('menus.List', [], 'ReportBundle'),
                #        'uri' => $this->getRouter()->generate('report.list_page'),
                #    ],
                #],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_REPORT_PRODUCT_VIEW' => [
                'group' => 'roles.groups.report',
                'label' => 'roles.report.product.view',
                'translation_domain' => 'ReportBundle',
            ],
            'ROLE_REPORT_CUSTOMER_VIEW' => [
                'group' => 'roles.groups.report',
                'label' => 'roles.report.customer.view',
                'translation_domain' => 'ReportBundle',
            ],
            'ROLE_REPORT_GATEWAY_VIEW' => [
                'group' => 'roles.groups.report',
                'label' => 'roles.report.gateway.view',
                'translation_domain' => 'ReportBundle',
            ],
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Report/product.html.twig',
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
