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
        
        if ($transaction->isDeposit() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->credit($transaction);
        } else if ($transaction->isBonus() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END ) {
            $this->credit($transaction);
        }
    }

    private function credit(Transaction $transaction): void 
    {
        $subTransactions = $transaction->getSubTransactions();
        $jwt = $this->jwtGenerator->generate([]);
        foreach ($subTransactions as $subTransaction) {
           $memberProduct = $subTransaction->getCustomerProduct();
           try {
                $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getName()));
                $integration->credit($jwt, [
                    'id' => $memberProduct->getUsername(),
                    'amount' => $subTransaction->getAmount(),
                    'transactionId' => $subTransaction->getParent()->getNumber()
                ]);
           } catch(NoSuchIntegrationException $ex) {
               continue;
           }
        }
    }

}


