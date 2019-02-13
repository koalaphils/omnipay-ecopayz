<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner;

class XPubReceiverAddress
{
    private $index;
    private $address;
    private $balance;
    private $used;

    public static function create(array $data): self
    {
        $instance = new self();

        $instance->index = (int) $data['index'];
        $instance->address = $data['address'];
        $instance->balance = (string) $data['balance'];
        $instance->used = $data['used'] ?? false;

        return $instance;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    private function __construct()
    {
        // Force to use create function
    }
}