<?php

namespace PaymentBundle\Component\Blockchain\Wallet;

class Account
{
    private $balance;
    private $label;
    private $index;
    private $archived;
    private $extendedPublicKey;
    private $extendedPrivateKey;
    private $receiveIndex;
    private $lastUsedReceiveIndex;
    private $receiveAddress;

    public static function create(array $data): self
    {
        $instance = new self();
        $instance->balance = (string) $data['balance'];
        $instance->label = $data['label'];
        $instance->index = (int) $data['index'];
        $instance->archived = (bool) $data['archived'];
        $instance->extendedPublicKey = $data['extendedPublicKey'];
        $instance->extendedPrivateKey = $data['extendedPrivateKey'];
        $instance->receiveIndex = (int) $data['receiveIndex'];
        $instance->receiveAddress = $data['receiveAddress'];
        $instance->lastUsedReceiveIndex = $data['lastUsedReceiveIndex'];

        return $instance;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function getExtendedPublicKey(): string
    {
        return $this->getExtendedPublicKey();
    }

    public function getExtendedPrivateKey(): string
    {
        return $this->getExtendedPrivateKey();
    }

    public function getReceiveIndex(): int
    {
        return $this->receiveIndex;
    }

    public function getLastUsedReceiveIndex(): ?int
    {
        return $this->lastUsedReceiveIndex;
    }

    public function getReceiveAddress(): string
    {
        return $this->receiveAddress;
    }

    private function __construct()
    {
    }
}
