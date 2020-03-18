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

use ProductIntegrationBundle\Exception\NoSuchIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use ProductIntegrationBundle\ProductIntegrationFactory;
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

    // TODO: Adjust transition to improve logic coherence
    public function onTransitionEntered(WorkflowEvent $event)
    {
        $transaction = $event->getSubject();
        $jwt = $this->jwtGenerator->generate([]);

        if ($event->getTransition()->getName() === 'void') {
            // $this->handleVoiding($jwt, $subTransactions);
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return; 
        }

        if ($transaction->includesPiwiWalletMemberProduct()) {
            $this->processPiwiWalletTransaction($transaction, $jwt);
        } else {
            $this->processTransaction($transaction, $jwt);
        }        
    }

    // This would be called if one of the involved subtransaction is 
    // PIWI Wallet
    private function processPiwiWalletTransaction($transaction, $jwt): void
    {   
        $subTransactions = $transaction->getSubTransactions();


        // Transfer flow is different
        if ($transaction->isTransfer()) {
            foreach ($subTransactions as $subTransaction) {
                // If the source product is piwi wallet
                if ($subTransaction->isWithdrawal() && $subTransaction->includesPiwiWalletMemberProduct()) {
                    $this->debitFromPiwiWallet($subTransaction, $jwt);
                }

                // If the source product is not piwi wallet
                if ($subTransaction->isWithdrawal() && !$subTransaction->includesPiwiWalletMemberProduct()) {
                    $this->debitFromIntegration($subTransaction, $jwt);
                }

                // If the destination product is piwi wallet
                if ($subTransaction->isDeposit() && $subTransaction->includesPiwiWalletMemberProduct()) {
                    $this->creditToPiwiWallet($subTransaction, $jwt);
                }

                 // If the destination product is not piwi wallet
                if ($subTransaction->isDeposit() && !$subTransaction->includesPiwiWalletMemberProduct()) {
                    $this->creditToIntegration($subTransaction, $jwt);
                }
            }
        } else {
            foreach ($subTransactions as $subTransaction) {
                if (!$subTransaction->getHasTransactedWithPiwiWalletMember()) {
                    if ($subTransaction->isDeposit()) {
                        $this->creditToPiwiWallet($subTransaction, $jwt);    
                    } else if ($subTransaction->isWithdrawal()) {
                        $this->debitFromPiwiWallet($subTransaction, $jwt);
                    }
                }
            }
        }   
    }

    private function processTransaction($transaction, $jwt): void
    {
        $subTransactions = $transaction->getSubTransactions();
        foreach ($subTransactions as $subTransaction) {
            if ($subTransaction->isDeposit()) {
                if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_ACKNOWLEDGE) {
                    $this->creditToPiwiWallet($subTransaction, $jwt);
                } else if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
                    if (!$subTransaction->getParent()->wasCreatedFromMemberSite()) {
                        $this->creditToPiwiWallet($subTransaction, $jwt);
                    }
                    
                    try {
                        $this->creditToIntegration($subTransaction, $jwt);
                        $this->debitFromPiwiWallet($subTransaction, $jwt);
                    } catch (IntegrationNotAvailableException $ex) {
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }
            } else if ($subTransaction->isWithdrawal()) {
                if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_ACKNOWLEDGE) {
                    $this->debitFromIntegration($subTransaction, $jwt);
                    $this->creditToPiwiWallet($subTransaction, $jwt);
                } else if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
                    if (!$subTransaction->getParent()->wasCreatedFromMemberSite()) {
                        $this->debitFromIntegration($subTransaction, $jwt);
                        $this->creditToPiwiWallet($subTransaction, $jwt);
                    }

                    $this->debitFromPiwiWallet($subTransaction, $jwt);
                }
            }
        }

        if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
            $this->gatewayMemberTransaction->processMemberTransaction($transaction);
        }  
    }

    private function creditToPiwiWallet($subTransaction, string $jwt)
    {
        $amount = $subTransaction->getAmount();
        $piwiIntegration = $this->factory->getIntegration('pwm');
        $piwiBalance = $piwiIntegration->credit($jwt, [
            'amount' => $amount,
            'member' => $subTransaction->getParent()->getCustomer(),
        ]);
        $subTransaction->setHasTransactedWithPiwiWalletMember(true);
    }
    
    private function debitFromPiwiWallet($subTransaction, string $jwt)
    {
        $amount = $subTransaction->getAmount();
        $piwiIntegration = $this->factory->getIntegration('pwm');
        $piwiBalance = $piwiIntegration->debit($jwt, [
            'amount' => $amount,
            'member' => $subTransaction->getParent()->getCustomer(),
        ]);

        $subTransaction->setHasTransactedWithPiwiWalletMember(true);
    }

    private function creditToIntegration($subTransaction, string $jwt)
    {
        $memberProduct = $subTransaction->getCustomerProduct();
        $integration = $this->factory->getIntegration(strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode()));
        $newBalance = $integration->credit($jwt, [
            'id' => $subTransaction->getCustomerProduct()->getUsername(),
            'amount' => $subTransaction->getAmount()
        ]);
        $memberProduct->setBalance($newBalance);
    }

    private function debitFromIntegration($subTransaction, string $jwt)
    {
        $memberProduct = $subTransaction->getCustomerProduct();
        $integration = $this->factory->getIntegration(strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode()));
        $newBalance = $integration->debit($jwt, [
            'id' => $subTransaction->getCustomerProduct()->getUsername(),
            'amount' => $subTransaction->getAmount()
        ]);
        $memberProduct->setBalance($newBalance);
    }
}


