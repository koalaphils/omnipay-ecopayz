<?php

namespace ProductBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ProductBundle extends Bundle
{
    public function registerMenu()
    {
        return [
            'product' => [
                'permission' => ['ROLE_PRODUCT_VIEW', 'ROLE_PRODUCT_CREATE'],
                'label' => $this->getTranslator()->trans('menus.Product', [], 'ProductBundle'),
                'uri' => 'javascript:void(0)',
                'icon' => 'icon-handbag',
                'subMenus' => [
                    'list' => [
                        'permission' => ['ROLE_PRODUCT_VIEW'],
                        'label' => $this->getTranslator()->trans('menus.List', [], 'ProductBundle'),
                        'uri' => $this->getRouter()->generate('product.list_page'),
                    ],
                    'create' => [
                        'permission' => ['ROLE_PRODUCT_CREATE'],
                        'label' => $this->getTranslator()->trans('menus.Create', [], 'ProductBundle'),
                        'uri' => $this->getRouter()->generate('product.create_page'),
                    ],
                ],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_PRODUCT_VIEW' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.view',
                'translation_domain' => 'ProductBundle',
            ],
            'ROLE_PRODUCT_CREATE' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.create',
                'translation_domain' => 'ProductBundle',
                'requirements' => ['ROLE_PRODUCT_VIEW'],
            ],
            'ROLE_PRODUCT_UPDATE' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.update',
                'translation_domain' => 'ProductBundle',
                'requirements' => ['ROLE_PRODUCT_VIEW'],
            ],
            'ROLE_PRODUCT_SUSPEND' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.suspend',
                'translation_domain' => 'ProductBundle',
                'requirements' => ['ROLE_PRODUCT_VIEW'],
            ],
            'ROLE_PRODUCT_ACTIVATE' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.activate',
                'translation_domain' => 'ProductBundle',
                'requirements' => ['ROLE_PRODUCT_ACTIVATE'],
            ],
            'ROLE_PRODUCT_DELETE' => [
                'group' => 'roles.groups.product',
                'label' => 'roles.product.delete',
                'translation_domain' => 'ProductBundle',
                'requirements' => ['ROLE_PRODUCT_VIEW', 'ROLE_PRODUCT_UPDATE']
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
