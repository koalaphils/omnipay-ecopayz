<?php

namespace PromoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PromoBundle extends Bundle
{
    public function registerMenu()
    {
        return [];
    }

    public function registerRole()
    {
        return [];
    }

    public function registerThemetViews()
    {
        return [];
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
