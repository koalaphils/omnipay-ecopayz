<?php

namespace DWLBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;

class DwlSearchWidget extends AbstractWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'DWL Search'
        ];
    }

    public function getCurrency()
    {
        return $this->getCurrencyRepository()->findAll();
    }

    public function getProduct()
    {
        return $this->getProductRepository()->getProductList();
    }

    protected function getView()
    {
        return 'DWLBundle:Widget:widget.html.twig';
    }

    protected function getBlockName(): string
    {
        return 'dwlSearch';
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->container->get('doctrine')->getRepository(\DbBundle\Entity\Currency::class);
    }

    private function getProductRepository(): \DbBundle\Repository\ProductRepository
    {
        return $this->container->get('doctrine')->getRepository(\DbBundle\Entity\Product::class);
    }
}
