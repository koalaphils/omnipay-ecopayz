<?php

declare(strict_types = 1);

namespace GatewayTransactionBundle\Listener;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use TransactionBundle\Event\TransactionProcessEvent;

class TransactionSubscriber implements EventSubscriberInterface
{
    /**
     * @var GatewayMemberTransaction
     */
    private $gatewayMemberTransaction;

    /**
     * @var SettingManager
     */
    private $settingManager;

    public static function getSubscribedEvents()
    {
        return [
            'transaction.post_save' => [
                ['onTransactionPostSave', 80],
            ],
        ];
    }

    public function __construct(GatewayMemberTransaction $gatewayMemberTransaction, SettingManager $settingManager)
    {
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
        $this->settingManager = $settingManager;
    }

    public function onTransactionPostSave(TransactionProcessEvent $event): void
    {
        $this->gatewayMemberTransaction->updateIdentifierByNumberAndClass(
            Transaction::class,
            $event->getTransaction()->getNumber(),
            (string) $event->getTransaction()->getId()
        );
    }
    
    private function getStatus($status)
    {
        $settingInfo = $this->settingManager->getSetting('transaction.status.' . $status);

        return $settingInfo;
    }
}