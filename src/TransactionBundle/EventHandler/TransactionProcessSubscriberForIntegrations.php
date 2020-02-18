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
use PinnacleBundle\Service\PinnacleService;

// TODO:
// Bitcoin Deposit, Voiding, Gateways

class TransactionProcessSubscriberForIntegrations implements EventSubscriberInterface
{
    private $factory;
    private $jwtGenerator;
    private $pinnacleService;

    public function __construct(ProductIntegrationFactory $factory, JWTGeneratorService $jwtGenerator, PinnacleService $pinnacleService)
    {
        $this->factory = $factory;
        $this->jwtGenerator = $jwtGenerator;
        $this->pinnacleService = $pinnacleService;
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
            return; // Do nothing after voiding a transaction.
        }


        if (($transaction->isDeposit() || $transaction->isBonus()) && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->handleDeposit($jwt, $subTransactions);
        } else if ($transaction->isWithdrawal() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->handleWithdrawal($jwt, $subTransactions);
        } else if ($transaction->isTransfer() && $transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            $this->handleTransfer($jwt, $subTransactions);
        }
    }

    private function handleDeposit(string $jwt, $subTransactions): void
    {
        $creditedTransactions = [];
        try {
            foreach ($subTransactions as $subTransaction) {
                $creditedTransactions[] = $this->credit($jwt, $subTransaction);
            }
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Credit amount on PIWI Wallet   
            throw $ex;
        }
    }

    private function handleWithdrawal(string $jwt, $subTransactions): void
    {
        $debitedTransactions = [];
        try {
            foreach ($subTransactions as $subTransaction) {
                $debitedTransactions = $this->debit($jwt, $subTransaction, $newTransactionId);
            }
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Credit amount on PIWI Wallet   
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
                    $this->debit($jwt, $subTransaction, 'void_' . uniqid());
                } else if ($subTransaction->isWithdrawal()) {
                    $this->credit($jwt, $subTransaction, 'void_' . uniqid());
                }
            }
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Handle Later
            throw $ex;
        }
    }

    
    private function handleTransfer(string $jwt, $subTransactions): void
    {
        try {
            foreach($subTransactions as $subTransaction) {
                $memberProduct = $subTransaction->getCustomerProduct();
                $subTransactionAmount = $subTransaction->getAmount();
                if ($subTransaction->isDeposit()) {
                    $this->credit($jwt, $subTransaction);
                } else if ($subTransaction->isWithdrawal()) {
                    $this->debit($jwt, $subTransaction);
                }
            }
        } catch (IntegrationNotAvailableException $ex) {
            // TODO: Handle Later
            
            throw $ex;
        }
    }

    // If $newTransactionId is present, it will be used
    // as the transaction id for crediting the user.
    // This means that the actual transactionId is already
    // processed by some integration and will throw an error
    private function credit(string $jwt, $subTransaction, $newTransactionId = null) 
    {
        $memberProduct = $subTransaction->getCustomerProduct();
        $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getCode()));
        $newBalance = $integration->credit($jwt, [
            'id' => $memberProduct->getUsername(),
            'amount' => $subTransaction->getAmount(),
            'transactionId' => $subTransaction->getParent()->getNumber(),
            'newTransactionId' => $newTransactionId
        ]);
        $memberProduct->setBalance($newBalance);

        return $subTransaction;
    }

    // If $newTransactionId is present, it will be used
    // as the transaction id for debiting the user.
    // This means that the actual transactionId is already
    // processed by some integration and will throw an error
    private function debit(string $jwt, $subTransaction, $newTransactionId = null)
    {
        $memberProduct = $subTransaction->getCustomerProduct();
        $integration = $this->factory->getIntegration(strtolower($memberProduct->getProduct()->getCode()));
        $newBalance = $integration->debit($jwt, [
            'id' => $memberProduct->getUsername(),
            'amount' => $subTransaction->getAmount(),
            'transactionId' => $subTransaction->getParent()->getNumber(),
            'newTransactionId' => $newTransactionId
        ]);
        $memberProduct->setBalance($newBalance);

        return $subTransaction;
    }
}


