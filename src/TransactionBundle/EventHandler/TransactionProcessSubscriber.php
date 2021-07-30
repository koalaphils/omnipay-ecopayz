<?php

namespace TransactionBundle\EventHandler;

use ApiBundle\Exceptions\FailedTransferException;
use DbBundle\Entity\Transaction;
use Exception;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TransactionBundle\Event\TransactionProcessEvent;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use Symfony\Component\Workflow\Event\GuardEvent as WorkflowGuardEvent;
use TransactionBundle\Exceptions\TransitionGuardException;

class TransactionProcessSubscriber implements EventSubscriberInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'transaction.saving' => [
                ['onTransactionSaving', 100],
            ],
            'workflow.transaction.guard' => [
                ['onTransitionGuard', 100],
            ],
            'workflow.transaction.guard.void' => [
                ['onTransitionGuardToVoid', 100],
            ],
            'workflow.transaction.entered.void' => [
                ['onTransitionToVoid', 100],
            ],
            'workflow.transaction.entered' => [
                ['onTransitionEntered', 100],
            ],
        ];
    }

    public function onTransactionSaving(TransactionProcessEvent $event)
    {
        $transaction = $event->getTransaction();
        $action = $event->getAction();

        //If a transaction was created from BO or a transfer transaction request was created from MWA
        if (($transaction->isNew() && !$event->fromCustomer()) || ($transaction->isNew() && $event->fromCustomer() && $transaction->isTransfer())) {
            $transitionName = 'new';
        } elseif ($transaction->isNew() && $event->fromCustomer() && !$transaction->isTransfer()) {
            $transitionName = 'customer-new';
        } else {
            $transitionName = $transaction->getStatus() . '_' . $action['status'];
        }

        if (!$event->isVoid()) {
            $toStatusInfo = $this->getStatus($action['status']);

            if (!$transaction->getCurrency()) {
                $transaction->setCurrency($transaction->getCustomer()->getCurrency());
            }

            $this->getTransactionManager()->processTransactionSummary($transaction);
            if ($transitionName === 'customer-new' && $event->fromCustomer() && $transaction->isDeposit()) {
                $this->getPaymentManager()->processPaymentOption($transaction);
            }
            
            $paymentOptionMode = \DbBundle\Entity\PaymentOption::PAYMENT_MODE_OFFLINE;
            if ($transaction->getPaymentOptionType() instanceof \DbBundle\Entity\PaymentOption) {
                $paymentOptionMode = $transaction->getPaymentOptionType()->getPaymentMode();
            }
    
            if ($this->getTransactionWorkflow()->can($transaction, $paymentOptionMode . '-' . $transitionName)) {
                $this->getTransactionWorkflow()->apply($transaction, $paymentOptionMode . '-' . '-' . $transitionName);
            } elseif ($this->getTransactionWorkflow()->can($transaction, $transaction->getTypeText() . '-' . $transitionName)) {
                $this->getTransactionWorkflow()->apply($transaction, $transaction->getTypeText() . '-' . $transitionName);
            } elseif ($this->getTransactionWorkflow()->can($transaction, $transitionName)) {
                $this->getTransactionWorkflow()->apply($transaction, $transitionName);
                if ($transitionName === 'new') {
                    $this->getTransactionWorkflow()->apply($transaction, 'acknowledge');
                    try {
                        $this->getTransactionWorkflow()->apply($transaction, 'process');
                    } catch (FailedTransferException $exception) {
                        $transaction->setDetail('transfer', $exception->getMessage());
                    } catch (Exception $exception) {
                        throw $exception;
                    }
                }
            } else {
                throw new TransitionGuardException('Unable to transition the transaction');
            }

            $transactionDate = new \DateTime('now');
            $transactionDate->setTimezone(new \DateTimeZone('UTC'));
            $transaction->setDetail('transaction_dates.' . $transaction->getStatus(), $transactionDate->format('c'));
        } else {            
            if ($this->getTransactionWorkflow()->can($transaction, 'void')) {
                $this->getTransactionWorkflow()->apply($transaction, 'void');
                $transactionDate = new \DateTime('now');
                $transactionDate->setTimezone(new \DateTimeZone('UTC'));
                $transaction->setDetail('transaction_dates.void', $transactionDate->format('c'));
            } else {
                throw new TransitionGuardException('Unable to void the transaction');
            }
        }
    }

    public function onTransitionEntered(WorkflowEvent $event)
    {
        /* @var $transaction Transaction */
        $transaction = $event->getSubject();
        $newStatus = $this->getStatus($transaction->getStatus());
        if ($event->getTransition()->getName() === 'void') {
            $this->getTransactionManager()->voidTransaction($transaction);
        } elseif (array_get($newStatus, 'end', false)) {
            $this->getTransactionManager()->endTransaction($transaction);
            $transaction->getCustomer()->setEnabled();
        }
    }

    public function onTransitionGuardToVoid(WorkflowGuardEvent $event)
    {
        $transaction = $event->getSubject();
        if ($transaction->isVoided()) {
            $event->setBlocked(true);
        }
    }

    public function onTransitionGuard(WorkflowGuardEvent $event)
    {
        $event->setBlocked(false);
    }

    private function getStatus($status)
    {
        $settingInfo = $this->getSettingManager()->getSetting('transaction.status.' . $status);

        return $settingInfo;
    }

    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->container->get('app.setting_manager');
    }

    private function getTransactionManager(): \TransactionBundle\Manager\TransactionManager
    {
        return $this->container->get('transaction.manager');
    }

    private function getTransactionWorkflow(): \Symfony\Component\Workflow\Workflow
    {
        return $this->container->get('transaction.workflow');
    }

    private function getPaymentManager(): \PaymentBundle\Manager\PaymentManager
    {
        return $this->container->get('payment.manager');
    }
}
