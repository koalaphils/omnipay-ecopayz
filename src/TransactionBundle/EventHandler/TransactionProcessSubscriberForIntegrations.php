<?php

/**
 * This subscriber listens for transaction events.
 * Depending on the event, this class performs
 * different operations on a Product Integration (e.g Evolution Service) service
 * associated with the dispatched CustomerProduct within
 * the Transaction Entity.
 */

namespace TransactionBundle\EventHandler;

use ApiBundle\Exceptions\FailedTransferException;
use ApiBundle\Service\JWTGeneratorService;
use AppBundle\Service\CustomerPaymentOptionService;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\MemberPromo;
use DbBundle\Entity\Product;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\MemberPromoRepository;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use GatewayTransactionBundle\Manager\GatewayMemberTransaction;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use ProductIntegrationBundle\ProductIntegrationFactory;
use TransactionBundle\Exceptions\NoPiwiWalletProductException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use Psr\Log\LoggerInterface;

class TransactionProcessSubscriberForIntegrations implements EventSubscriberInterface
{
    private $factory;
    private $jwtGenerator;
    private $gatewayMemberTransaction;
    private $customerProductRepository;
    private $cpoService;
    private $logger;

    public function __construct(
        ProductIntegrationFactory $factory,
        JWTGeneratorService $jwtGenerator,
        GatewayMemberTransaction $gatewayMemberTransaction,
        CustomerProductRepository $customerProductRepository,
        CustomerPaymentOptionService $cpoService,
        LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->jwtGenerator = $jwtGenerator;
        $this->gatewayMemberTransaction = $gatewayMemberTransaction;
        $this->customerProductRepository = $customerProductRepository;
        $this->cpoService = $cpoService;
        $this->logger = $logger;
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
        $this->logger->info('ON TRANSITION ENTERED');
        $jwt = $this->jwtGenerator->generate([]);
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();
        $transitionName = $event->getTransition()->getName();
        $transactionNumber = $transaction->getNumber();
        $currency = $transaction->getCurrency()->getCode();
        $subTransactions = $transaction->getSubtransactions();
        $customerPiwiWalletProduct = $this->getCustomerPiwiWalletProduct($transaction->getCustomer());
        if ($customerPiwiWalletProduct === null) {
            throw new NoPiwiWalletProductException($transaction->getCustomer());
        }
        $customerWalletCode = $customerPiwiWalletProduct->getProduct()->getCode() ?? $customerPiwiWalletProduct->getProduct()->getCodeByName($customerPiwiWalletProduct->getProduct()->getName());

        // Acknowledged
        if ($transaction->getStatus() === Transaction::TRANSACTION_STATUS_ACKNOWLEDGE) {
            /** @var SubTransaction $subTransaction */
	        foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $productName = $subTransaction->getCustomerProduct()->getProduct()->getName();

                if ($productCode === '') {
                    $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCodeByName($productName));
                }

                if ($subTransaction->isDeposit()) {
                    if ($transaction->isTransferDestinationPiwiWalletProduct()) {
                        $subTransaction->setHasBalanceAdjusted(true);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        if (!($transaction->isTransferSourcePiwiWalletProduct() || $subTransaction->isPiwiWalletMemberProduct())) {
							$amount += $subTransaction->getCustomerFee();
                            $newBalance = $this->debit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                            $subTransaction->getCustomerProduct()->setBalance($newBalance);
                            $subTransaction->setHasBalanceAdjusted(true);
                            $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                    } catch (DebitIntegrationException $ex) {
                        $this->logError(__LINE__, $ex);
                        if ($transaction->isTransfer()) {
                            throw new DebitIntegrationException('An error occurred while getting funds from ' . $productName, 422);
                        }
                        throw $ex->getPrevious() ?? $ex;
                    } catch (CreditIntegrationException $ex) {
                        $this->logError(__LINE__, $ex);
                        if (!$transaction->isTransferSourcePiwiWalletProduct()) {
                            $newBalance = $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                            $customerPiwiWalletProduct->setBalance($newBalance);

                            throw $ex;
                        }
                        throw $ex->getPrevious() ?? $ex;
                    } catch (Exception $ex) {
                        $this->logError(__LINE__, $ex);
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
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $productName = $subTransaction->getCustomerProduct()->getProduct()->getName();

                if ($productCode === '') {
                    $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCodeByName($productName));
                }
                
                if ($subTransaction->isDeposit()) {
                    try {
                        if (!$transaction->isTransferDestinationPiwiWalletProduct()) {
                            if (!$transaction->isDeposit() && !$transaction->isBonus()) {
                                $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                            }
                            $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                            $subTransaction->getCustomerProduct()->setBalance($newBalance);
                            $subTransaction->setHasBalanceAdjusted(true);
                        }
                    } catch (DebitIntegrationException $ex) {
                        $this->logError(__LINE__, $ex);
                        throw  $ex->getPrevious() ?? $ex;
                    } catch (CreditIntegrationException $ex) {
                        $this->logError(__LINE__, $ex);
                        if ($transaction->isTransfer() && !$transaction->isTransferDestinationPiwiWalletProduct()) {
                            $newBalance = $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                            $customerPiwiWalletProduct->setBalance($newBalance);
                            $subTransaction->setFailedProcessingWithIntegration(false); 
                
                            throw new FailedTransferException(Transaction::DETAIL_TRANSFER_FAILED_TO . '_' . $productName, 422);
                        }
	                    throw $ex;
                    } catch (Exception $ex) {
                        $this->logError(__LINE__, $ex);
                        $subTransaction->setFailedProcessingWithIntegration(true);
                        throw $ex;
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
                        if (!$transaction->isTransfer()) {
	                        $amount += $subTransaction->getCustomerFee();
                            $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                    } catch (DebitIntegrationException $ex) {
                        $this->logError(__LINE__, $ex);
                        throw $ex->getPrevious() ?? $ex;
                    } catch (Exception $ex) {
                        $this->logError(__LINE__, $ex);
                        $subTransaction->setFailedProcessingWithIntegration(true);
                        throw $ex;
                    }
                }
            }

            if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
                $this->gatewayMemberTransaction->processMemberTransaction($transaction);
            }

            
            if ($transaction->isDeposit() || $transaction->isWithdrawal()) {
                $this->cpoService->update($transaction->getCustomer()->getId(), ['paymentOption' => $transaction->getPaymentOptionOnTransaction()]);
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
                    try {
	                    $amount += $subTransaction->getCustomerFee();
                        //Debit transferred funds from PIWI Wallet.
                        $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        //Credit from Product Wallet to rollback funds.
                        $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    } catch (DebitIntegrationException $ex) {
                        throw $ex->getPrevious() ?? $ex;
                    } catch (CreditIntegrationException $ex) {
                        if (!$transaction->isTransferDestinationPiwiWalletProduct()) {
                            $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                        throw $ex->getPrevious() ?? $ex;
                    } catch (Exception $ex) {
                        $subTransaction->setFailedProcessingWithIntegration(true);
                        throw $ex;
                    }
                }
            }

            if ($transaction->isDeposit() || $transaction->isBonus() || $transaction->isWithdrawal()) {
                $this->gatewayMemberTransaction->processMemberTransaction($transaction);
            }
        }

        //void
        if ($event->getTransition()->getName() === 'void') {
            
            if ($transaction->isTransfer() && count($subTransactions) === 2) {
                list($subTransactions[0], $subTransactions[1]) = [$subTransactions[1], $subTransactions[0]];
            }

            foreach ($subTransactions as $subTransaction) {
                $amount = $subTransaction->getAmount();
                $productCode = strtolower($subTransaction->getCustomerProduct()->getProduct()->getCode());
                $customerProductUsername = $subTransaction->getCustomerProduct()->getUsername();

                if ($subTransaction->isDeposit()) {
                    try {
                        $newBalance = $this->debit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    } catch (DebitIntegrationException $ex) {
                        throw $ex->getPrevious() ?? $ex;
                    } catch (IntegrationNotAvailableException $ex) {
                        if (!$transaction->getCustomer()->getIsAffiliate()) {
                            $this->debit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        }
                        $subTransaction->setFailedProcessingWithIntegration(true);
                    }
                }

                if ($subTransaction->isWithdrawal()) {
                    try {
	                    $amount += $subTransaction->getCustomerFee();
                        $newBalance = $this->credit($productCode, $transactionNumber, $currency, $customerProductUsername, $amount, $jwt);
                        $subTransaction->getCustomerProduct()->setBalance($newBalance);
                    } catch (CreditIntegrationException $ex) {
                        $this->credit($customerWalletCode, $transactionNumber, $currency, $customerPiwiWalletProduct->getUsername(), $amount, $jwt);
                        throw $ex->getPrevious() ?? $ex;
                    } catch (Exception $ex) {
                        $subTransaction->setFailedProcessingWithIntegration(true);
                        throw $ex;
                    }
                }
            }

            $this->gatewayMemberTransaction->voidMemberTransaction($transaction);
            return;
        }
    }

    private function logError($line, $ex)
    {
        $this->logger->info('TRANSACTION SUBSCRIBER ERROR IN ' . $line);
        $this->logger->debug($ex);
    }

    private function getCustomerPiwiWalletProduct(Member $member): ?CustomerProduct
    {
        $wallet = Product::MEMBER_WALLET_CODE;
        if ($member->getUser()->getType() == User::USER_TYPE_AFFILIATE) {
            $wallet = Product::AFFILIATE_WALLET_CODE;
        }
        return $this->customerProductRepository->getMemberPiwiMemberWallet($member, $wallet);
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
}