<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Request\Transaction\TransferRequest;
use ApiBundle\Request\Transaction\TransactionItemRequest;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerProductRepository;
use Doctrine\Common\Collections\ArrayCollection;

class TransferHandler
{
    private $customerProductRepository;

    public function __construct(CustomerProductRepository $customerProductRepository)
    {
        $this->customerProductRepository = $customerProductRepository;
    }

    public function handle(TransferRequest $request)
    {
        $sourceCustomerProduct = $this->customerProductRepository->findById($request->getFrom());

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
        $fromSubTransaction->setAmount($total);
        $fromSubTransaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
        $fromSubTransaction->setImmutableCustomerProductData($sourceCustomerProduct->getUsername());

        // Combine toSubTransactions and fromSubTransactions into an ArrayCollection
        array_unshift($toSubtransactions[], $fromSubTransaction);
        // $subTransactions = new ArrayCollection($toSubtransactions);
        
        // Create Transaction
        $transaction = new Transaction();
        $transaction->setSubTransactions($toSubtransactions);
        $transaction->setType(Transaction::TRANSACTION_TYPE_TRANSFER);

        dump($transaction);
    }
}