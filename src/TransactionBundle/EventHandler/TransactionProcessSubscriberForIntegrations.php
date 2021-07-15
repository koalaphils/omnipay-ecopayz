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
use DbBundle\Entity\Product;
use DbBundle\Entity\User;
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
        $jwt = $this->jwtGenerator->generate([]);
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();
        $transitionName = $event->getTransition()->getName();
        $transactionNumber = $transaction->getNumber();
        $currency = $transaction->getCurrency()->getCode();
        $subTransactions = $transaction->getSubtransactions();
        $customerPiwiWalletProduct = $this->getCustomerPiwiWalletProduct($transaction->getCustomer());
        $customerWalletCode = $customerPiwiWalletProduct->getProduct()->getCode();


        // Acknowledged
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_ACKNOWLEDGE) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                if ($subTransaction->isDeposit()) {
                    if ($transaction->isTransferDestinationPiwiWalletProduct()){
                        $subTransaction->setHasBalanceAdjusted(true);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        if(!($transaction->isTransferSourcePiwiWalletProduct() || $subTransaction->isPiwiWalletMemberProduct())){
                            $newBalance = $this->debit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                            $subTransaction->getCustomerProduct()->setBalance($newBalance);
                            $subTransaction->setHasBalanceAdjusted(true);
                            $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                    } catch (IntegrationNotAvailableException $ex) {
                        $subTransaction->setFailedProcessingWithIntegration(true);
                        throw $ex;
                    }
                }
            }
        }

        //Processed
        if (($transaction->getStatus() === Transaction::TRANSACTION_STATUS_END) && ($event->getTransition()->getName() !== 'void')) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                if ($subTransaction->isDeposit()) {
                    try {
                        if(!$transaction->isTransferDestinationPiwiWalletProduct()){
                            if (!$transaction->isDeposit() && !$transaction->isBonus()){
                                $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                            }
                            $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                            $subTransaction->getCustomerProduct()->setBalance($newBalance);
                            $subTransaction->setHasBalanceAdjusted(true);
                        }
                    } catch (IntegrationNotAvailableException $ex) {
                        if(!$transaction->isTransferDestinationPiwiWalletProduct()){
                            $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        if (!$transaction->isTransfer()) {
                            $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                    } catch (IntegrationNotAvailableException $ex) {
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }
            }
            
            if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
                $this->gatewayMemberTransaction->processMemberTransaction($transaction);
            } 
        }

        // Declined
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_DECLINE) {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                 // If the transition is from Acknowledged to Declined
                if ($subTransaction->isWithdrawal() && $transitionName == Transaction::TRANSACTION_STATUS_ACKNOWLEDGE . '_' . Transaction::TRANSACTION_STATUS_DECLINE) {
                    $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                    $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                }
            }

            if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
                $this->gatewayMemberTransaction->processMemberTransaction($transaction);
            } 
        }

        //void
        if ($event->getTransition()->getName() === 'void') {
            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                if ($subTransaction->isDeposit()) {
                    try {
                        $newBalance = $this->debit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    } catch (IntegrationNotAvailableException $ex) {
                        if (!$transaction->getCustomer()->getIsAffiliate()) {
                            $this->debit($customerWalletCode,$transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    } catch (IntegrationNotAvailableException $ex) {
                        $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }
            }

            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return; 
        }
    }


    private function credit(string $productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt): string
    {
        $integration = $this->factory->getIntegration(strtolower($productCode));
        $newBalance = $integration->credit($jwt, [
            'id' => $customerProductUsername,
            'amount' => $amount,
            'transactionNumber' => $transactionNumber,
            'currency' => $currency
        ]);
        
        return $newBalance;
    }

    private function debit(string $productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt): string
    {
        $integration = $this->factory->getIntegration(strtolower($productCode));
        $newBalance = $integration->debit($jwt, [
            'id' => $customerProductUsername,
            'amount' => $amount,
            'transactionNumber' => $transactionNumber,
            'currency' => $currency
        ]);
        
        return $newBalance;
    }

    private function getCustomerPiwiWalletProduct(Member $member): CustomerProduct
    {
        $wallet = Product::MEMBER_WALLET_CODE;
        if ($member->getUser()->getType() == User::USER_TYPE_AFFILIATE) {
            $wallet = Product::AFFILIATE_WALLET_CODE;
        }
        return $this->customerProductRepository->getMemberPiwiMemberWallet($member, $wallet);
    }
}