<?php

namespace ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Transaction;

class TransactionCreatedEvent extends Event
{
    private $transaction;
    
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
    
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
