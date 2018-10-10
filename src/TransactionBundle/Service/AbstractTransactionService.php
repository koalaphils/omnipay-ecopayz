<?php

namespace TransactionBundle\Service;

use Psr\Log\LoggerInterface;
use DbBundle\Entity\DWL;
use TransactionBundle\WebsocketTopics;
use TransactionBundle\Repository\TransactionRepository;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Entity\PaymentOption;
use AppBundle\Manager\SettingManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;

abstract class AbstractTransactionService
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function reloadTransactionTables(): void
    {
        $this->sendCommandToReloadWithGeneratedData();
    }

    protected function sendCommandToReloadWithGeneratedData(): void
    {
        $generatedData = json_encode(
            [
                'topic' => WebsocketTopics::TOPIC_TRANSACTION_DECLINED,
                'args' => [[
                    'forTableReload' => true,
                ]],
            ]
        );
        $generatedData = "'" . $generatedData . "'";

        $command = [
            'curl -H "Content-Type: application/json"',
            '-d',
            $generatedData,
            $this->getWampService() . '/pub &'
        ];

        $this->log(implode(' ', $command));
        shell_exec(implode(' ', $command));
    }

    protected function log(string $message)
    {
        if (!$this->hasLogger()) {
            return;
        }

        $this->logger->info($message, []);
    }

    protected function hasLogger(): bool
    {
        return $this->logger instanceof LoggerInterface;
    }

    protected function getTransactionRepository(): TransactionRepository
    {
        return $this->getService('transaction.transaction_repository');
    }

    protected function getPaymentOptionRepository(): PaymentOptionRepository
    {
        return $this->getEntityManager()->getRepository(PaymentOption::class);
    }

    protected function getSettingManager(): SettingManager
    {
        return $this->getService('app.setting_manager');
    }

    protected function getDoctrine(): RegistryInterface
    {
        return $this->getService('doctrine');
    }

    protected function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function getService(string $serviceName)
    {
        return $this->container->get($serviceName);
    }

    protected function getWampService()
    {
        return $this->container->getParameter('websocket.wamp');
    }

    protected function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }
}
