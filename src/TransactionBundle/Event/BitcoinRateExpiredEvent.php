<?php

namespace TransactionBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Transaction;

class BitcoinRateExpiredEvent extends Event
{
    const NAME = 'transaction.bitcoin_rate_expired';

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
