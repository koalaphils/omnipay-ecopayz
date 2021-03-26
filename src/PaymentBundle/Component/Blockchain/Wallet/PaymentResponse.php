<?php

namespace PaymentBundle\Component\Blockchain\Wallet;

class PaymentResponse
{
    private $to;
    private $from;
    private $amounts;
    private $fee;
    private $transactionId;
    private $success;

    public static function create(array $data): self
    {
        $instance = new self();
        $instance->to = $data['to'] ?? [];
        $instance->from = $data['from'] ?? [];
        $instance->amounts = [];
        $instance->amounts = $data['amounts'] ?? [];
        $instance->fee = (string) ($data['fee'] ?? '0');
        $instance->transactionId = $data['txid'];
        $instance->success = (bool) $data['success'];

        return $instance;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function getAmounts(): array
    {
        return $this->amounts;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    private function __construct()
    {
    }
}
