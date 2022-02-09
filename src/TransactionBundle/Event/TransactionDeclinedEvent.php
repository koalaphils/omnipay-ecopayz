<?php

declare(strict_types = 1);

namespace TransactionBundle\Event;

use DbBundle\Entity\Transaction;
use Symfony\Component\EventDispatcher\Event;

class TransactionDeclinedEvent extends Event
{
    public const NAME='transaction.declined';

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
