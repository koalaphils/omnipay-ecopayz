<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

class GetLastBitcoinRequest
{
    /**
     * @var int
     */
    private $memberId;
    private $type;

    public function __construct(int $memberId, string $type)
    {
        $this->memberId = $memberId;
        $this->type = $type;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}