<?php

declare(strict_types = 1);

namespace TransactionBundle\Event;

use DbBundle\Entity\Transaction;
use Symfony\Component\EventDispatcher\Event;

class TransactionPreDeclineEvent extends Event
{
    /**
     * @var Transaction
     */
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