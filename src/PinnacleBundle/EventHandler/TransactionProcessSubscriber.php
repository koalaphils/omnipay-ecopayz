<?php

declare(strict_types = 1);

namespace PinnacleBundle\EventHandler;

use AppBundle\Manager\SettingManager;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use Doctrine\Common\Collections\Collection;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use PaymentBundle\Event\NotifyEvent;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

class TransactionProcessSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    /**
     * @var GatewayMemberTransaction
     */
    private $gatewayMemberTransaction;

    public static function getSubscribedEvents()
    {
        return [
            'workflow.transaction.entered' => [
                ['onTransitionEntered', 90],
            ],
            NotifyEvent::EVENT_NAME => [['onBitcoinNotified', 100]],
        ];
    }

    public function __construct(SettingManager $settingManager, PinnacleService $pinnacleService, GatewayMemberTransaction $gatewayMemberTransaction)
    {
        $this->settingManager = $settingManager;
        $this->pinnacleService = $pinnacleService;
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
    }

    public function onBitcoinNotified(NotifyEvent $event): void
    {
        $transaction = $event->getTransaction();
        if (!$transaction->isVoided()
            && $transaction->isDeposit()
            && $transaction->getStatus() == $this->settingManager->getSetting('pinnacle.transaction.deposit.status')
        ) {
            $this->processSubtransactions($transaction);
        }
    }

    public function onTransitionEntered(WorkflowEvent $event)
    {
        /* @var $transaction Transaction */
        $transaction = $event->getSubject();
        if ($event->getTransition()->getName() === 'void') {
            $this->processSubtransactions($transaction, true);
        } elseif ($transaction->isDeposit() && $transaction->getStatus() == $this->settingManager->getSetting('pinnacle.transaction.deposit.status')) {
            $this->processSubtransactions($transaction);
        } elseif ($transaction->isWithdrawal() && $transaction->getStatus() == $this->settingManager->getSetting('pinnacle.transaction.withdraw.status')) {
            $this->processSubtransactions($transaction);
        } elseif ($transaction->isWithdrawal() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->processSubtransactions($transaction);
        } elseif ($transaction->isDeclined()) {
            $this->processSubtransactions($transaction, true);
        }
    }

    /**
     * @param SubTransaction[] $subTransactions
     */
    private function processSubtransactions(Transaction $transaction, bool $voided = false): void
    {
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();

        $subTransactions = $transaction->getSubTransactions();
        foreach ($subTransactions as $subTransaction) {
            $memberProduct = $subTransaction->getCustomerProduct();
            $subTransactionAmount = Number::round($subTransaction->getDetail('convertedAmount', $subTransaction->getAmount()), 2, Number::ROUND_DOWN);
            if (
                ($subTransaction->isDeposit() && !$voided && !$subTransaction->getDetail('pinnacle.transacted', false))
                || ($subTransaction->isWithdrawal() && $voided && $subTransaction->getDetail('pinnacle.transacted', false))
            ) {
                if ($pinnacleProduct->getCode() === $memberProduct->getProduct()->getCode()) {
                    $this->pinnacleService->getTransactionComponent()->deposit($memberProduct->getUsername(), $subTransactionAmount);
                    $subTransaction->setDetail('pinnacle.transacted', true);
                }
            } elseif (
                ($subTransaction->isWithdrawal() && !$voided && !$subTransaction->getDetail('pinnacle.transacted', false))
                || ($subTransaction->isDeposit() && $voided && $subTransaction->getDetail('pinnacle.transacted', false))
            ) {
                if ($pinnacleProduct->getCode() === $memberProduct->getProduct()->getCode()) {
                    $this->pinnacleService->getTransactionComponent()->withdraw($memberProduct->getUsername(), $subTransactionAmount);
                    $subTransaction->setDetail('pinnacle.transacted', true);
                }
            }
        }

        if ($voided && ($transaction->isDeposit() || $transaction->isWithdrawal())) {
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
        } elseif (!$voided && ($transaction->isDeposit() || $transaction->isWithdrawal())) {
            $this->gatewayMemberTransaction->processMemberTransaction($transaction);
        }
    }
}