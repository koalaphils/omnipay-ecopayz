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
        $subTransactions = $transaction->getSubTransactions();
        $jwt = $this->jwtGenerator->generate([]);

        if ($event->getTransition()->getName() === 'void') {
            // $this->handleVoiding($jwt, $subTransactions);
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return; 
        }

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
}


