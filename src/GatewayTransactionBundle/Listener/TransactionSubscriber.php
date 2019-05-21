<?php

declare(strict_types = 1);

namespace GatewayTransactionBundle\Listener;

use AppBundle\Manager\SettingManager;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

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
            'workflow.transaction.entered' => [
                ['onTransitionEntered', 80],
            ],
        ];
    }

    public function __construct(GatewayMemberTransaction $gatewayMemberTransaction, SettingManager $settingManager)
    {
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
        $this->settingManager = $settingManager;
    }

    public function onTransitionEntered(Event $event): void
    {
        /* @var $transaction Transaction */
        $transaction = $event->getSubject();
        $newStatus = $this->getStatus($transaction->getStatus());

        if ($event->getTransition()->getName() === 'void') {
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
        } elseif (array_get($newStatus, 'end', false)) {
            $this->gatewayMemberTransaction->processMemberTransaction($transaction);
        }
    }



    private function getStatus($status)
    {
        $settingInfo = $this->settingManager->getSetting('transaction.status.' . $status);

        return $settingInfo;
    }
}