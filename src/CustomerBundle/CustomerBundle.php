<?php

namespace CustomerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomerBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'customer' => [
                'permission' => ['ROLE_CUSTOMER_VIEW', 'ROLE_CUSTOMER_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Customer', [], 'CustomerBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-user',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_CUSTOMER_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.Customer List', [], 'CustomerBundle'),
                        'uri' => $this->getRouter()->generate('customer.list_page'),
                    ],
                    'affiliate' => [
                        'permission' => ['ROLE_CUSTOMER_VIEW', 'ROLE_CUSTOMER_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Affiliate List', [], 'CustomerBundle'),
                        'uri' => $this->getRouter()->generate('affiliate.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_CUSTOMER_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'CustomerBundle'),
                        'uri' => $this->getRouter()->generate('customer.create_page'),
                    ],
                    'groupList' => [
                        'permission' => ['ROLE_CUSTOMER_GROUP_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.Customer Group', [], 'CustomerGroupBundle'),
                        'uri' => $this->getRouter()->generate('customer.group_list_page'),
                    ],
                    'groupCreate' => [
                        'permission' => ['ROLE_CUSTOMER_GROUP_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Customer Group Create', [], 'CustomerGroupBundle'),
                        'uri' => $this->getRouter()->generate('customer.group_create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_CUSTOMER_VIEW' => [
                'group' => 'roles.groups.customer',
                'label' => 'roles.customer.view',
                'translation_domain' => 'CustomerBundle',
                'requirements' => ['ROLE_CURRENCY_VIEW', 'ROLE_COUNTRY_VIEW', 'ROLE_PRODUCT_VIEW', 'ROLE_PAYMENTOPTION_VIEW'],
            ],
            'ROLE_CUSTOMER_CREATE' => [
                'group' => 'roles.groups.customer',
                'label' => 'roles.customer.create',
                'translation_domain' => 'CustomerBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ],
            'ROLE_CUSTOMER_UPDATE' => [
                'group' => 'roles.groups.customer',
                'label' => 'roles.customer.update',
                'translation_domain' => 'CustomerBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ],
            'ROLE_CUSTOMER_PRODUCT_VIEW' => [
                'group' => [
                    'text' => 'roles.groups.customer',
                    'translation_domain' => 'CustomerBundle',
                ],
                'label' => 'roles.customerProduct.view',
                'translation_domain' => 'CustomerProductBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ],
            'ROLE_CUSTOMER_PRODUCT_UPDATE' => [
                'group' => [
                    'text' => 'roles.groups.customer',
                    'translation_domain' => 'CustomerBundle',
                ],
                'label' => 'roles.customerProduct.update',
                'translation_domain' => 'CustomerProductBundle',
                'requirements' => ['ROLE_CUSTOMER_PRODUCT_VIEW'],
            ],
            'ROLE_CUSTOMER_PRODUCT_CREATE' => [
                'group' => [
                    'text' => 'roles.groups.customer',
                    'translation_domain' => 'CustomerBundle',
                ],
                'label' => 'roles.customerProduct.create',
                'translation_domain' => 'CustomerProductBundle',
                'requirements' => ['ROLE_CUSTOMER_PRODUCT_VIEW'],
            ],
            'ROLE_CUSTOMER_GROUP_VIEW' => [
                'group' => [
                    'text' => 'roles.groups.customer',
                    'translation_domain' => 'CustomerBundle',
                ],
                'label' => 'roles.customer_group.view',
                'translation_domain' => 'CustomerGroupBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ],
            'ROLE_CONVERT_TO_CUSTOMER' => [
                'group' => 'roles.groups.customer',
                'label' => 'roles.customer.convert_to_customer',
                'translation_domain' => 'CustomerBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ],
            'ROLE_CONVERT_TO_AFFILIATE' => [
                'group' => 'roles.groups.customer',
                'label' => 'roles.customer.convert_to_affiliate',
                'translation_domain' => 'CustomerBundle',
                'requirements' => ['ROLE_CUSTOMER_VIEW'],
            ]
        ];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/update.html.twig',
            'Default/create.html.twig',
            'Product/index.html.twig',
            'Modal/create.html.twig',
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
