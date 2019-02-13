<?php

namespace TransactionBundle\Service;

use Psr\Log\LoggerInterface;
use DbBundle\Entity\DWL;
use TransactionBundle\WebsocketTopics;
use DbBundle\Repository\TransactionRepository;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Repository\UserRepository;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\User;
use DbBundle\Entity\Transaction;
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

    protected function reloadTransactionTables(array $transactionIds = []): void
    {
        $this->sendCommandToReloadWithGeneratedData($transactionIds);
    }

    protected function sendCommandToReloadWithGeneratedData(array $transactionIds = []): void
    {
        $generatedData = json_encode(
            [
                'topic' => WebsocketTopics::TOPIC_TRANSACTION_DECLINED,
                'args' => [[
                    'forTableReload' => true,
                    'forPageReload' => true,
                    'transactionIds' => $transactionIds,
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
        return $this->getEntityManager()->getRepository(Transaction::class);
    }

    protected function getPaymentOptionRepository(): PaymentOptionRepository
    {
        return $this->getEntityManager()->getRepository(PaymentOption::class);
    }

    protected function getSettingManager(): SettingManager
    {
        return $this->getService('app.setting_manager');
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
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
