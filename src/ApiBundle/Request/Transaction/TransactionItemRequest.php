<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

/**
 * TODO: Use this class to other "TransactionRequests" class 
 * instead of Product class 
 */
class TransactionItemRequest
{
    private $id;
    private $amount;

    public function __construct(?int $id, ?string $amount)
    {
        $this->id = $id;
        $this->amount = $amount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }
}