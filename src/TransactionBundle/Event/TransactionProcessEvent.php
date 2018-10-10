<?php

namespace TransactionBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Transaction;

class TransactionProcessEvent extends Event
{
    private $transaction;
    private $action;
    private $fromCustomer;
    private $reasonOfPropagationStopped;

    public function __construct(Transaction $transaction, array $action = [], bool $fromCustomer = false)
    {
        $this->transaction = $transaction;
        $this->action = $action;
        $this->fromCustomer = $fromCustomer;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getAction(): array
    {
        return $this->action;
    }

    public function transactionIsNew(): bool
    {
        return $this->transaction->getId() === null;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response): self
    {
        $this->response = $response;

        return $this;
    }

    public function isVoid(): bool
    {
        return ($this->action['status'] === 'void');
    }

    public function fromCustomer(): bool
    {
        return $this->fromCustomer;
    }

    public function getMembersInSubTransactions(): array
    {
        $subTransactions = $this->getSubTransactions();

        $members = [];
        foreach ($subTransactions as $subTransaction) {
            $members[] = $subTransaction->getCustomerProduct()->getCustomer();
        }

        return $members;
    }

    private function getSubTransactions()
    {
        $subTransactions = $this->getTransaction()->getSubTransactions();

        return $subTransactions;
    }
}
