<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Event\TransactionCreatedEvent;
use ApiBundle\Exceptions\FailedTransferException;
use ApiBundle\Request\Transaction\TransferRequest;
use ApiBundle\Request\Transaction\TransactionItemRequest;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\CreditIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationException\DebitIntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TransactionBundle\Manager\TransactionManager;

class TransferHandler
{
    private $customerProductRepository;
    private $memberPaymentOptionRepository;
    private $transactionManager;
    private $eventDispatcher;
    private $em;

    public function __construct(
        CustomerProductRepository $customerProductRepository,
        CustomerPaymentOptionRepository $memberPaymentOptionRepository,
        TransactionManager $transactionManager,
        EventDispatcherInterface $eventDispatcher,
        EntityManager $em)
    {
        $this->customerProductRepository = $customerProductRepository;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
        $this->transactionManager = $transactionManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
    }

    public function handle(TransferRequest $request)
    {
        try {
            $this->em->beginTransaction();
            $member = $request->getMember();
            $sourceCustomerProduct = $this->customerProductRepository->findById($request->getFrom());
            $response = ['error' => false, 'status' => 200, 'message' => ''];
    
            // Form array of SubTransactions consisting of destination CustomerProducts
            $toSubtransactions =  array_map(function(TransactionItemRequest $item) {
                $customerProduct =  $this->customerProductRepository->findById($item->getId());
                $subTransaction = new SubTransaction();
                $subTransaction->setCustomerProduct($customerProduct);
                $subTransaction->setAmount($item->getAmount());
                $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
                $subTransaction->setImmutableCustomerProductData($customerProduct->getUsername());
                
                return $subTransaction;
            }, $request->getTo());
    
            $total = new Number(0);
            foreach ($toSubtransactions as $subTransaction) {
                $total = $total->plus($subTransaction->getAmount());
            }
    
            // Create a Subtransaction based on the source CustomerProduct;
            $fromSubTransaction = new SubTransaction();
            $fromSubTransaction->setCustomerProduct($sourceCustomerProduct);
            $fromSubTransaction->setAmount($total->toString());
            $fromSubTransaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
            $fromSubTransaction->setImmutableCustomerProductData($sourceCustomerProduct->getUsername());
    
            // Combine toSubTransactions and fromSubTransaction
            array_unshift($toSubtransactions, $fromSubTransaction);
            
            // Create Transaction
            $transaction = new Transaction();
            $transaction->setNumber($this->transactionManager->generateTransactionNumber('transfer'));
            $transaction->setSubTransactions($toSubtransactions);
            $transaction->setType(Transaction::TRANSACTION_TYPE_TRANSFER);
            $transaction->setCustomer($member);
            $transaction->setDate(new \DateTime());
            $transaction->setFee('customer_fee', 0);
            $transaction->setFee('company_fee', 0);
    
            // Get CustomerPaymentOption
            // $memberPaymentOption = $this->memberPaymentOptionRepository->findByFields((int) $member->getId(), $request->getPaymentOptionType());
            // $transaction->setPaymentOption($memberPaymentOption);
            // $transaction->autoSetPaymentOptionType();
            // $transaction->setPaymentOptionOnTransaction($memberPaymentOption);
            $transaction->retainImmutableData();
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];

            $this->transactionManager->processTransaction($transaction, $action, true);

            if ($transaction->getDetail('transfer')) {
                $transferDetails = explode('_', $transaction->getDetail('transfer'));
                if ($transferDetails[0] === Transaction::DETAIL_TRANSFER_FAILED_TO) {
                    $response['message'] = 'An error occurred while transferring funds to ' . $transferDetails[1] . '.  Your funds were transferred to PIWI Wallet instead.';
                    $response['hasCallback'] = true;
                    $response['error'] = true;
                    $response['status'] = 422;
                } 
            }

            $this->em->commit();

            $event = new TransactionCreatedEvent($transaction);
            $this->eventDispatcher->dispatch('transaction.created', $event);
  
            return $response;

        } catch(IntegrationNotAvailableException $exception) {
            $this->em->rollback();
            return ['message' => $exception->getMessage(), 'error' => true, 'status' => $exception->getCode()];
        } catch(CreditIntegrationException $exception) {
            $this->em->rollback();
            return ['message' => $exception->getMessage(), 'error' => true, 'status' => $exception->getCode()];
        } catch(DebitIntegrationException $exception) {
            $this->em->rollback();
            return ['message' => $exception->getMessage(), 'error' => true, 'status' => $exception->getCode()];
        } catch (\Exception $exception) {
            $this->em->rollback();
            throw $exception;
        }
    }
}