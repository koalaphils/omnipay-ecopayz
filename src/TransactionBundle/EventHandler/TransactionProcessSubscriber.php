<?php

namespace TransactionBundle\EventHandler;

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
        if ($transaction->isNew() && !$event->fromCustomer()) {
            $transitionName = 'new';
        } elseif ($transaction->isNew() && $event->fromCustomer()) {
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

            if ($this->getTransactionWorkflow()->can($transaction, $transaction->getTypeText() . '-' . $transitionName)) {
                $this->getTransactionWorkflow()->apply($transaction, $transaction->getTypeText() . '-' . $transitionName);
            } else if ($this->getTransactionWorkflow()->can($transaction, $transitionName)) {
                $this->getTransactionWorkflow()->apply($transaction, $transitionName);
            } else {
                throw new TransitionGuardException('Unable to transition the transaction');
            }
        } else {
            if ($this->getTransactionWorkflow()->can($transaction, 'void')) {
                $this->getTransactionWorkflow()->apply($transaction, 'void');
            } else {
                throw new TransitionGuardException('Unable to void the transaction');
            }
        }
    }

    public function onTransitionEntered(WorkflowEvent $event)
    {
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
