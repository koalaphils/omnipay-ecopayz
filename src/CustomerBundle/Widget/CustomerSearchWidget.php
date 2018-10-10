<?php

namespace CustomerBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\HttpFoundation\Response;

class CustomerSearchWidget extends AbstractWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'Member Search'
        ];
    }

    public function getCurrency()
    {
        return $this->getCurrencyRepository()->findAll();
    }

    public function getCountry()
    {
        return $this->getCountryRepository()->findAll();
    }

    protected function getView()
    {
        return 'CustomerBundle:Widget:widget.html.twig';
    }

    protected function getBlockName(): string
    {
        return 'customersearch';
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->container->get('doctrine')->getRepository(\DbBundle\Entity\Currency::class);
    }

    private function getCountryRepository(): \DbBundle\Repository\CountryRepository
    {
        return $this->container->get('doctrine')->getRepository(\DbBundle\Entity\Country::class);
    }
}
