<?php

/**
 * This subscriber listens for transaction events.
 * Depending on the event, this class performs
 * different operations on a Product Integration (e.g Evolution Service) service
 * associated with the dispatched CustomerProduct within
 * the Transaction Entity.
 */

namespace TransactionBundle\EventHandler;

use ApiBundle\ProductIntegration\NoSuchIntegrationException;
use ApiBundle\ProductIntegration\ProductIntegrationFactory;
use ApiBundle\Service\JWTGeneratorService;
use DbBundle\Entity\Transaction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

class TransactionProcessSubscriberForIntegrations implements EventSubscriberInterface
{
    private $factory;
    private $jwtGenerator;

    public function __construct(ProductIntegrationFactory $factory, JWTGeneratorService $jwtGenerator)
    {
        $this->factory = $factory;
        $this->jwtGenerator = $jwtGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.transaction.entered' => [
                ['onTransitionEntered', 90],
            ],
        ];
    }

    public function onTransitionEntered(WorkflowEvent $event)
    {
        $transaction = $event->getSubject();
        $subTransactions = $transaction->getSubTransactions();
        $jwt = $this->jwtGenerator->generate([]);
        
        if (($transaction->isDeposit() || $transaction->isBonus()) && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->credit($jwt, $subTransactions);
        } else if ($transaction->isWithdrawal() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->debit($jwt, $subTransactions);
        }
    }

    private function credit(string $jwt, $subTransactions): void 
    {
        foreach ($subTransactions as $subTransaction) {
           $memberProduct = $subTransaction->getCustomerProduct();
           try {
                $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getName()));
                $newBalance = $integration->credit($jwt, [
                    'id' => $memberProduct->getUsername(),
                    'amount' => $subTransaction->getAmount(),
                    'transactionId' => $subTransaction->getParent()->getNumber()
                ]);
                $memberProduct->setBalance($newBalance);
           } catch(NoSuchIntegrationException $ex) {
               continue;
           }
        }
    }

    private function debit(string $jwt, $subTransactions): void 
    {
        foreach ($subTransactions as $subTransaction) {
           $memberProduct = $subTransaction->getCustomerProduct();
           try {
                $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getName()));
                $newBalance = $integration->debit($jwt, [
                    'id' => $memberProduct->getUsername(),
                    'amount' => $subTransaction->getAmount(),
                    'transactionId' => $subTransaction->getParent()->getNumber()
                ]);
                $memberProduct->setBalance($newBalance);
           } catch(NoSuchIntegrationException $ex) {
               continue;
           }
        }
    }
}


