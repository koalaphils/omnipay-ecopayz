<?php

namespace ApiBundle\Manager;

use AppBundle\Manager\AbstractManager;
use ApiBundle\Model\Transaction as TransactionModel;
use ApiBundle\Model\SubTransaction as SubTransactionModel;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\CustomerPaymentOption;
use DbBundle\Entity\SubTransaction;
use ApiBundle\Model\Transfer as TransferModel;
use ApiBundle\Event\TransactionCreatedEvent;

class TransactionManager extends AbstractManager
{
    public function handleDeposit(TransactionModel $transactionModel)
    {
        $transaction = new Transaction();
        // zimi
        $customer = $transactionModel->getCustomer();        
        $transaction->setCustomer($customer);
        $transaction->setPaymentOption($transactionModel->getPaymentOption());
        $transaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
        $transaction->setNumber(date('Ymd-His-') . $this->getTransactionManager()->getType('deposit'));
        $transaction->setDate(new \DateTime());
        $transaction->setFee('customer_fee', $transactionModel->getCustomerFee());
        $transaction->setFee('company_fee', 0);
        $transaction->setDetail('email', $transactionModel->getEmail());
        // zimi        
        $transaction->setAmount($transactionModel->getAmount());
        // $transaction->setAmount(40000);

        $transaction->autoSetPaymentOptionType();

        // zimi-comment
        foreach ($transactionModel->getSubTransactions() as $subTransactionModel) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($subTransactionModel->getProduct());
            $subTransaction->setAmount($subTransactionModel->getAmount());
            $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);

            $transaction->addSubTransaction($subTransaction);
        }

        $transaction->setPaymentOptionOnTransaction($this->createPaymentOptionOnTransaction($transactionModel));
        $transaction->retainImmutableData();

        $this->beginTransaction();
        try {
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];            
            $this->getTransactionManager()->processTransaction($transaction, $action, true);
            $this->commit();

            $event = new TransactionCreatedEvent($transaction);
            $this->get('event_dispatcher')->dispatch('transaction.created', $event);
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $transaction;
    }

    // namdo
    public function handleWithdraw(TransactionModel $transactionModel)
    {
        $transaction = new Transaction();
        $transaction->setCustomer($transactionModel->getCustomer());
        $transaction->setPaymentOption($transactionModel->getPaymentOption());
        $transaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
        $transaction->setNumber(date('Ymd-His-') . $this->getTransactionManager()->getType('withdraw'));
        $transaction->setDate(new \DateTime());
        $transaction->setFee('company_fee', 0);
        $transaction->setFee('customer_fee', $transactionModel->getCustomerFee());
        $transaction->autoSetPaymentOptionType();
        // zimi        
        $transaction->setAmount($transactionModel->getAmount());

        foreach ($transactionModel->getSubTransactions() as $subTransactionModel) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($subTransactionModel->getProduct());
            $subTransaction->setAmount($subTransactionModel->getAmount());
            $subTransaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
            $subTransaction->setDetail('hasFee', $subTransactionModel->getForFee());

            $transaction->addSubTransaction($subTransaction);
        }
        $transaction->setPaymentOptionOnTransaction($this->createPaymentOptionOnTransaction($transactionModel));
        $transaction->retainImmutableData();

        $this->beginTransaction();
        try {
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
            $this->getTransactionManager()->processTransaction($transaction, $action, true);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $transaction;
    }

    public function handleTransfer(TransferModel $transferModel)
    {
        $transaction = new Transaction();
        $transaction->setCustomer($transferModel->getCustomer());
        $transaction->setType(Transaction::TRANSACTION_TYPE_TRANSFER);
        $transaction->setNumber(date('Ymd-His-') . $this->getTransactionManager()->getType('transfer'));
        $transaction->setDate(new \DateTime());

        $from = new SubTransaction();
        $from->setCustomerProduct($transferModel->getFrom());
        $from->setAmount($transferModel->getTotalAmount());
        $from->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
        $transaction->addSubTransaction($from);

        foreach ($transferModel->getTo() as $toModel) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($toModel->getProduct());
            $subTransaction->setAmount($toModel->getAmount());
            $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
            $transaction->addSubTransaction($subTransaction);
        }
        $transaction->retainImmutableData();

        $this->beginTransaction();
        try {
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
            $this->getTransactionManager()->processTransaction($transaction, $action, true);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $transaction;
    }

    public function handleP2PTransfer(TransferModel $transferModel)
    {
        $transaction = new Transaction();
        $transaction->setCustomer($transferModel->getCustomer());
        $transaction->setType(Transaction::TRANSACTION_TYPE_P2P_TRANSFER);
        $transaction->setNumber(date('Ymd-His-') . $this->getTransactionManager()->getType('transfer'));
        $transaction->setDate(new \DateTime());

        $from = new SubTransaction();
        $from->setCustomerProduct($transferModel->getFrom());
        $from->setAmount($transferModel->getTotalAmount());
        $from->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
        $transaction->addSubTransaction($from);

        $transaction->setDetail('toCustomer', $transferModel->getToCustomer());

        foreach ($transferModel->getTo() as $toModel) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($toModel->getProduct());
            $subTransaction->setAmount($toModel->getAmount());
            $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
            $transaction->addSubTransaction($subTransaction);
        }
        $transaction->retainImmutableData();

        $this->beginTransaction();
        try {
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
            $this->getTransactionManager()->processTransaction($transaction, $action, true);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $transaction;
    }

    public function enableCustomerPaymentOption(int $customerPaymentOptionId): void
    {
        $customerPaymentOption = $this->getCustomerPaymentOptionRepository()->find($customerPaymentOptionId);
        $customerPaymentOption->enable();
        $this->getEntityManager()->persist($customerPaymentOption);
        $this->getEntityManager()->flush($customerPaymentOption);
    }

    public function addCustomerPaymentOption(\DbBundle\Entity\Customer $customer, string $paymentOptionType, array $transaction = []):? \DbBundle\Entity\CustomerPaymentOption
    {
        $paymentOption = $this->getPaymentOptionRepository()->find($paymentOptionType);
        
        $customerPaymentOption = new CustomerPaymentOption();
        $customerPaymentOption->setPaymentOption($paymentOption);
        $customerPaymentOption->setCustomer($customer);
        if ($paymentOption->isPaymentEcopayz()) {
            $customerPaymentOption->addField('account_id', empty($transaction['email']) ? '' : $transaction['email']);
        } else {
            $customerPaymentOption->addField('account_id', null);
        }
        $customerPaymentOption->addField('email', empty($transaction['email']) ? '' : $transaction['email']);
        $this->getEntityManager()->persist($customerPaymentOption);
        $this->getEntityManager()->flush($customerPaymentOption);

        return $customerPaymentOption;
    }

    private function createPaymentOptionOnTransaction(TransactionModel $transactionModel): \DbBundle\Entity\CustomerPaymentOption
    {
        $paymentOption = $transactionModel->getPaymentOption();

        if ($paymentOption->getPaymentOption()->getPaymentMode() == 'offline') {
            $email = $transactionModel->getEmail();

            if ($email) {
                $customerPaymentOption = $this->getCustomerPaymentOptionRepository()->findByCustomerPaymentOptionAndEmail(
                    $transactionModel->getCustomer()->getId(),
                    $paymentOption->getPaymentOption()->getCode(),
                    $email
                );

                if (!$customerPaymentOption) {
                    $customerPaymentOption = new CustomerPaymentOption();
                    $customerPaymentOption->setPaymentOption($paymentOption->getPaymentOption());
                    $customerPaymentOption->setCustomer($transactionModel->getCustomer());
                    $customerPaymentOption->addField('email', $transactionModel->getEmail());
                    $this->getEntityManager()->persist($customerPaymentOption);
                }

                $paymentOption = $customerPaymentOption;
            }
        }

        return $paymentOption;
    }

    protected function getRepository(): \ApiBundle\Repository\TransactionRepository
    {
        return $this->getContainer()->get('api.transaction_repository');
    }

    private function getCustomerPaymentOptionRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerPaymentOption');
    }

    private function getTransactionManager(): \TransactionBundle\Manager\TransactionManager
    {
        return $this->getContainer()->get('transaction.manager');
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:PaymentOption');
    }
}
