<?php

namespace PaymentOptionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PaymentOptionBundle extends Bundle
{
    public function registerSettingMenu()
    {
        return [
            'paymentOption' => [
                'label' => 'settings.paymentOption.label::AppBundle',
                'description' => 'settings.paymentOption.label::AppBundle',
                'uri' => $this->getRouter()->generate('paymentoption.list_page'),
                'permission' => ['ROLE_VIEW_PAYMENTOPTIONS'],
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_PAYMENTOPTION_VIEW' => [
                'group' => 'roles.groups.paymentOption',
                'label' => 'roles.paymentoption.view',
                'translation_domain' => 'PaymentOptionBundle',
                'requirements' => ['ROLE_UPDATE_SETTINGS'],
            ],
            'ROLE_PAYMENTOPTION_CREATE' => [
                'group' => 'roles.groups.paymentOption',
                'label' => 'roles.paymentoption.create',
                'translation_domain' => 'PaymentOptionBundle',
                'requirements' => ['ROLE_PAYMENTOPTION_VIEW'],
            ],
            'ROLE_PAYMENTOPTION_UPDATE' => [
                'group' => 'roles.groups.paymentOption',
                'label' => 'roles.paymentoption.update',
                'translation_domain' => 'PaymentOptionBundle',
                'requirements' => ['ROLE_PAYMENTOPTION_VIEW'],
            ],
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
