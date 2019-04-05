<?php

declare(strict_types = 1);

namespace PinnacleBundle\EventHandler;

use AppBundle\Manager\SettingManager;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use Doctrine\Common\Collections\Collection;
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

    public static function getSubscribedEvents()
    {
        return [
            'workflow.transaction.entered' => [
                ['onTransitionEntered', 90],
            ],
        ];
    }

    public function __construct(SettingManager $settingManager, PinnacleService $pinnacleService)
    {
        $this->settingManager = $settingManager;
        $this->pinnacleService = $pinnacleService;
    }

    public function onTransitionEntered(WorkflowEvent $event)
    {
        /* @var $transaction Transaction */
        $transaction = $event->getSubject();
        if ($event->getTransition()->getName() === 'void') {
            $this->processSubtransactions($transaction->getSubTransactions(), true);
        } elseif ($transaction->isDeposit() && $transaction->getStatus() == $this->settingManager->getSetting('pinnacle.transaction.deposit.status')) {
            $this->processSubtransactions($transaction->getSubTransactions());
        } elseif ($transaction->isWithdrawal() && $transaction->getStatus() == $this->settingManager->getSetting('pinnacle.transaction.withdraw.status')) {
            $this->processSubtransactions($transaction->getSubTransactions());
        }
    }

    /**
     * @param SubTransaction[] $subTransactions
     */
    private function processSubtransactions(Collection $subTransactions, bool $voided = false): void
    {
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
        foreach ($subTransactions as $subTransaction) {
            $memberProduct = $subTransaction->getCustomerProduct();
            $subTransactionAmount = Number::round($subTransaction->getDetail('convertedAmount', $subTransaction->getAmount()), 2, Number::ROUND_DOWN);
            if (
                ($subTransaction->isDeposit() && !$voided)
                || ($subTransaction->isWithdrawal() && $voided && $subTransaction->getDetail('pinnacle.transacted', false))
            ) {
                if ($pinnacleProduct->getCode() === $memberProduct->getProduct()->getCode()) {
                    $this->pinnacleService->getTransactionComponent()->deposit($memberProduct->getUsername(), $subTransactionAmount);
                    $subTransaction->setDetail('pinnacle.transacted', true);
                }
            } elseif (
                ($subTransaction->isWithdrawal() && !$voided)
                || ($subTransaction->isDeposit() && $voided && $subTransaction->getDetail('pinnacle.transacted', false))
            ) {
                if ($pinnacleProduct->getCode() === $memberProduct->getProduct()->getCode()) {
                    $this->pinnacleService->getTransactionComponent()->withdraw($memberProduct->getUsername(), $subTransactionAmount);
                    $subTransaction->setDetail('pinnacle.transacted', true);
                }
            }
        }
    }
}