<?php

/**
 * This subscriber listens for transaction events.
 * Depending on the event, this class performs
 * different operations on a Product Integration (e.g Evolution Service) service
 * associated with the dispatched CustomerProduct within
 * the Transaction Entity.
 */

namespace TransactionBundle\EventHandler;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

use ApiBundle\ProductIntegration\NoSuchIntegrationException;
use ApiBundle\ProductIntegration\IntegrationNotAvailableException;
use ApiBundle\ProductIntegration\ProductIntegrationFactory;
use ApiBundle\Service\JWTGeneratorService;
use DbBundle\Entity\Transaction;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use PinnacleBundle\Service\PinnacleService;

class TransactionProcessSubscriberForIntegrations implements EventSubscriberInterface
{
    private $factory;
    private $jwtGenerator;
    private $pinnacleService;
    private $gatewayMemberTransaction;

    public function __construct(
        ProductIntegrationFactory $factory, 
        JWTGeneratorService $jwtGenerator, 
        PinnacleService $pinnacleService,
        GatewayMemberTransaction $gatewayMemberTransaction)
    {
        $this->factory = $factory;
        $this->jwtGenerator = $jwtGenerator;
        $this->pinnacleService = $pinnacleService;
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
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
        
        if ($event->getTransition()->getName() === 'void') {
            $this->handleVoiding($jwt, $subTransactions);
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return; // Do nothing after voiding a transaction.
        }

        try {
            if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
                foreach ($subTransactions as $subTransaction) {
                    if ($subTransaction->isDeposit()) {
                        $this->credit($jwt, $subTransaction->getAmount(), $subTransaction->getCustomerProduct());
                    } else if ($subTransaction->isWithdrawal()) {
                        $this->debit($jwt, $subTransaction->getAmount(), $subTransaction->getCustomerProduct());
                    }
                }
            }

            if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
                $this->gatewayMemberTransaction->processMemberTransaction($transaction);
            }
            
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Handle later
            throw $ex;
        }
    }

    private function handleVoiding(string $jwt, $subTransactions): void
    {
        try {
            foreach($subTransactions as $subTransaction) {
                $memberProduct = $subTransaction->getCustomerProduct();
                $subTransactionAmount = $subTransaction->getAmount();
                if ($subTransaction->isDeposit()) {
                    $this->debit($jwt, $subTransaction->getAmount(), $subTransaction->getCustomerProduct());
                } else if ($subTransaction->isWithdrawal()) {
                    $this->credit($jwt, $subTransaction->getAmount(), $subTransaction->getCustomerProduct());
                }
            }
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Handle Later
            throw $ex;
        }
    }

    private function credit(string $jwt, string $amount, $memberProduct)
    {
        dump($memberProduct->getProduct()->getCode());
        $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getCode()));
        $newBalance = $integration->credit($jwt, [
            'id' => $memberProduct->getUsername(),
            'amount' => $amount
        ]);
        $memberProduct->setBalance($newBalance);
    }

    private function debit(string $jwt, string $amount, $memberProduct)
    {
        $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getCode()));
        $newBalance = $integration->debit($jwt, [
            'id' => $memberProduct->getUsername(),
            'amount' => $amount
        ]);
        $memberProduct->setBalance($newBalance);
    }
}


