<?php

namespace PaymentBundle\Event;

use DbBundle\Entity\Transaction;
use Symfony\Component\EventDispatcher\Event;

class NotifyEvent extends Event
{
    public const EVENT_NAME = 'bitcoin.notified';

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var array
     */
    private $details;

    public function __construct(Transaction $transaction, array $details)
    {
        $this->transaction = $transaction;
        $this->details = $details;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getDetail(string $key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }
}