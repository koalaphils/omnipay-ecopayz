<?php

namespace DWLBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use DbBundle\Entity\Transaction;

class DWLBundle extends Bundle
{
    /**
     * Register Menu.
     *
     * @return array
     */
    public function registerMenu()
    {
        return [
            'dwl' => [
                'permission' => ['ROLE_DWL_VIEW', 'ROLE_DWL_CREATE'],
                'label' => 'menus.dwl',
                'translation_domain' => 'DWLBundle',
                'uri' => 'javascript:void(0)',
                'icon' => 'ti-calendar',
                'subMenus' => [
                    'lits' => [
                        'permission' => ['ROLE_DWL_VIEW'],
                        'label' => 'menus.List',
                        'translation_domain' => 'DWLBundle',
                        'uri' => ['name' => 'dwl.list_page'],
                    ],
                    'create' => [
                        'permission' => ['ROLE_DWL_CREATE'],
                        'label' => 'menus.Create',
                        'translation_domain' => 'DWLBundle',
                        'uri' => ['name' => 'dwl.create_page'],
                    ],
                ],
            ],
        ];
    }

    public function registerDefaultSetting()
    {
        return [
            'transaction.equations' => [
                'dwl' => [
                    'totalAmount' => [
                        'equation' => 'x+y',
                        'variables' => ['x' => 'sum_products', 'y' => 'total_customer_fee'],
                    ],
                    'customerAmount' => [
                        'equation' => 'x+y',
                        'variables' => ['x' => 'sum_products', 'y' => 'total_customer_fee'],
                    ],
                ],
            ],
            'transaction.type' => [
                'start' => ['dwl' => Transaction::TRANSACTION_STATUS_END],
                'workflow' => [
                    'dwl' => [
                        Transaction::TRANSACTION_STATUS_END => [
                            'actions' => [['label' => 'approve', 'status' => Transaction::TRANSACTION_STATUS_END]],
                        ],
                    ],
                ],
            ],
            'dwl' => [],
        ];
    }

    public function registerSettingCodes()
    {
        return ['dwl'];
    }

    /**
     * Register Roles.
     *
     * @return array
     */
    public function registerRole()
    {
        return [
            'ROLE_DWL_VIEW' => [
                'group' => 'roles.groups.dwl',
                'label' => 'roles.dwl.view',
                'translation_domain' => 'DWLBundle',
            ],
            'ROLE_DWL_CREATE' => [
                'group' => 'roles.groups.dwl',
                'label' => 'roles.dwl.create',
                'translation_domain' => 'DWLBundle',
                'requirements' => ['ROLE_DWL_VIEW', 'ROLE_PRODUCT_VIEW', 'ROLE_CURRENCY_VIEW'],
            ],
            'ROLE_DWL_UPDATE' => [
                'group' => 'roles.groups.dwl',
                'label' => 'roles.dwl.update',
                'translation_domain' => 'DWLBundle',
                'requirements' => ['ROLE_DWL_VIEW', 'ROLE_PRODUCT_VIEW', 'ROLE_CURRENCY_VIEW'],
            ],
        ];
    }

    /**
     * Register theme views.
     *
     * @return array
     */
    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/update.html.twig',
            'Default/create.html.twig',
        ];
    }
}
