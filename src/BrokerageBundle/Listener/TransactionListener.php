<?php

namespace BrokerageBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use Symfony\Component\Process\Process;
use TransactionBundle\Event\TransactionProcessEvent;

class TransactionListener
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function onTransactionSaved(TransactionProcessEvent $event)
    {
        $transaction = $event->getTransaction();
        if ($transaction instanceof Transaction) {
            if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END && $transaction->getType() !== Transaction::TRANSACTION_TYPE_DWL) {
                $userId = $transaction->getCreatedBy();
                $rootDir = $this->container->get('kernel')->getRootDir();
                $process = new Process("nohup " . $this->container->getParameter('php_command') . " $rootDir/console betadmin:sync-customer " . $transaction->getId() . " " . $userId . " --env=" . $this->container->get('kernel')->getEnvironment() . " &");
                $process->start();
            }
        }

        return;
    }

    private function getBrokerageManager(): \BrokerageBundle\Manager\BrokerageManager
    {
        return $this->container->get('brokerage.brokerage_manager');
    }
}
