<?php

declare (strict_types = 1);

namespace TransactionBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class TransactionStopDeclineEvent extends Event
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var string
     */
    private $reason;

    public function __construct(Transaction $transaction, string $reason)
    {
        $this->transaction = $transaction;
        $this->reason = $reason;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}