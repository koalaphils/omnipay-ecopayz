<?php

namespace TransactionBundle\Factory;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TransactionWorkflowFactory
{
    public static function generateTransactionWorkflow(SettingManager $settingManager, EventDispatcherInterface $eventDispatcher): Workflow
    {
        $statuses = $settingManager->getSetting('transaction.status');
        $startStatus = $settingManager->getSetting('transaction.start.admin');
        $customerStartStatus = $settingManager->getSetting('transaction.start.customer');

        $definitionBuilder = new DefinitionBuilder();
        $actions = [];

        foreach ($statuses as $key => $status) {
            $definitionBuilder->addPlace($key);
            foreach (array_get($status, 'actions', []) as $akey => $action) {
                $actions[$key . '_' . $action['status']] = ['from' => $key, 'to' => $action['status']];
            }
        }

        $definitionBuilder->addTransition(new Transition('new', Transaction::TRANSACTION_STATUS_START, $startStatus));
        $definitionBuilder->addTransition(new Transition('customer-new', Transaction::TRANSACTION_STATUS_START, $customerStartStatus));

        // void
        $voidTransition = new Transition('void', Transaction::TRANSACTION_STATUS_END, Transaction::TRANSACTION_STATUS_END);
        $definitionBuilder->addTransition($voidTransition);

        // workflow for types
        $startTypeStatus = $settingManager->getSetting('transaction.type.start', []);
        $typeWorkflows = $settingManager->getSetting('transaction.type.workflow', []);
        foreach ($startTypeStatus as $key => $startStatus) {
            $definitionBuilder->addTransition(new Transition($key . '-new', Transaction::TRANSACTION_STATUS_START, $startStatus));
        }

        foreach ($typeWorkflows as $type => $statuses) {
            foreach ($statuses as $key => $status) {
                $definitionBuilder->addPlace($key);
                foreach (array_get($status, 'actions', []) as $akey => $action) {
                    $actions[$type . '-' . $key . '_' . $action['status']] = ['from' => $key, 'to' => $action['status']];
                }
            }
        }

        foreach ($actions as $key => $action) {
            $transition = new Transition($key, $action['from'], $action['to']);
            $definitionBuilder->addTransition($transition);
        }

        $definition = $definitionBuilder->build();
        $marking = new SingleStateMarkingStore('status');

        return new Workflow($definition, $marking, $eventDispatcher, 'transaction');
    }
}
