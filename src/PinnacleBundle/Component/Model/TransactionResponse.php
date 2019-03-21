<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Model;


class TransactionResponse
{
    /**
     * @var array
     */
    private $data;

    public static function create(array $data): self
    {
        $instance = new static();
        $instance->data = $data;

        return $instance;
    }

    public function userCode(): string
    {
        return $this->data['userCode'];
    }

    public function loginId(): string
    {
        return $this->data['loginId'];
    }

    public function availableBalance(): string
    {
        return $this->data['availableBalance'];
    }

    public function amount(): string
    {
        return (string) $this->data['amount'];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}