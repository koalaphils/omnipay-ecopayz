<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

class GetLastBitcoinRequest
{
    /**
     * @var int
     */
    private $memberId;

    public function __construct(int $memberId)
    {
        $this->memberId = $memberId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }
}