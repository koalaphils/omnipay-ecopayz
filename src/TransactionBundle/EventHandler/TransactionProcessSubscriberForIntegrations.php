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

use ApiBundle\Service\JWTGeneratorService;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Customer as Member;
Use DbBundle\Repository\CustomerProductRepository;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use ProductIntegrationBundle\Exception\NoSuchIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use ProductIntegrationBundle\ProductIntegrationFactory;

class TransactionProcessSubscriberForIntegrations implements EventSubscriberInterface
{
    private $factory;
    private $jwtGenerator;
    private $pinnacleService;
    private $gatewayMemberTransaction;
    private $customerProductRepository;

    public function __construct(
        ProductIntegrationFactory $factory, 
        JWTGeneratorService $jwtGenerator,
        GatewayMemberTransaction $gatewayMemberTransaction,
        CustomerProductRepository $customerProductRepository)
    {
        $this->factory = $factory;
        $this->jwtGenerator = $jwtGenerator;
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
        $this->customerProductRepository = $customerProductRepository;
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
        $jwt = $this->jwtGenerator->generate([]);

        if ($event->getTransition()->getName() === 'void') {
            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return; 
        }

        $transitionName = $event->getTransition()->getName();
        $subTransactions = $transaction->getSubtransactions();
        $customerPiwiWalletProduct = $this->getCustomerPiwiWalletProduct($transaction->getCustomer());

        // Acknowledged
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_ACKNOWLEDGE) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();
    
                if ($subTransaction->isDeposit()) {
                    try {
                        $this->credit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    } catch (IntegrationNotAvailableException $ex) {
                        $this->credit('pwm',  $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        $newBalance = $this->debit($productCode, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                        $subTransaction->setHasBalanceAdjusted(true);
                        $this->credit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    } catch (IntegrationNotAvailableException $ex) {
                        $this->credit('pwm',  $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    }
                }
            }
        }

        // Processed
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();
    
                if ($subTransaction->isDeposit()) {
                    try {
                        $newBalance = $this->credit($productCode, $customerProductUsername, $amount, $jwt);   
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                        $subTransaction->setHasBalanceAdjusted(true);
                        $this->debit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);            
                    } catch (IntegrationNotAvailableException $ex) {
                        $this->credit('pwm',  $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        $this->debit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    } catch (IntegrationNotAvailableException $ex) {
                        $this->credit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                    }
                }
            }
        }

        // Declined
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_DECLINE) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                // If the transition is from Acknowledged to Declined
                if ($subTransaction->isDeposit() && $transitionName == Transaction::TRANSACTION_STATUS_ACKNOWLEDGE . '_' . Transaction::TRANSACTION_STATUS_DECLINE) {
                    $this->debit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                }

                 // If the transition is from Acknowledged to Declined
                if ($subTransaction->isWithdrawal() && $transitionName == Transaction::TRANSACTION_STATUS_ACKNOWLEDGE . '_' . Transaction::TRANSACTION_STATUS_DECLINE) {
                    $newBalance = $this->credit($productCode, $customerProductUsername, $amount, $jwt);
                    $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    $this->debit('pwm', $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                }
            }
        }
       
        if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
            $this->gatewayMemberTransaction->processMemberTransaction($transaction);
        }  
    }


    private function credit(string $productCode, $customerProductUsername, $amount, $jwt): string
    {
        $integration = $this->factory->getIntegration(strtolower($productCode));
        $newBalance = $integration->credit($jwt, [
            'id' => $customerProductUsername,
            'amount' => $amount 
        ]);
        
        return $newBalance;
    }

    private function debit(string $productCode,  $customerProductUsername, $amount, $jwt): string
    {
        $integration = $this->factory->getIntegration(strtolower($productCode));
        $newBalance = $integration->debit($jwt, [
            'id' => $customerProductUsername,
            'amount' => $amount
        ]);
        
        return $newBalance;
    }

    private function getCustomerPiwiWalletProduct(Member $member): CustomerProduct
    {
        return $this->customerProductRepository->getMemberPiwiMemberWallet($member, 'PWM');
    }
}


