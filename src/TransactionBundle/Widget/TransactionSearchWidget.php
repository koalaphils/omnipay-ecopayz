<?php

namespace TransactionBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionSearchWidget extends AbstractWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'Transaction Search',
        ];
    }

    public function getCurrency(): array
    {
        return $this->getCurrencyRepository()->findAll();
    }

    public function getTransactionStatuses(): array
    {
        return $this->getSettingManager()->getSetting('transaction.status');
    }

    protected function getView(): string
    {
        return 'TransactionBundle:Widget:widget.html.twig';
    }

    protected function getBlockName(): string
    {
        return 'transactionSearch';
    }

    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->container->get('app.setting_manager');
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->container->get('doctrine')->getRepository(\DbBundle\Entity\Currency::class);
    }
}
