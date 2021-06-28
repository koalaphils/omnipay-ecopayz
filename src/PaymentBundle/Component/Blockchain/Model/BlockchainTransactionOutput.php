<?php

namespace PaymentBundle\Component\Blockchain\Model;

use PaymentBundle\Component\Blockchain\BitcoinConverter;

class BlockchainTransactionOutput
{
    private $spent;
    private $txIndex;
    private $type;
    private $address;
    private $value;
    private $valueInSatoshi;
    private $n;
    private $script;

    public static function create(array $data): self
    {
        $output = new BlockchainTransactionOutput();
        $output->spent = $data['spend'] ?? false;
        $output->txIndex = $data['tx_index'] ?? null;
        $output->type = $data['type'] ?? null;
        $output->address = $data['addr'] ?? null;
        $output->n = $data['n'] ?? null;
        $output->script = $data['script'] ?? null;
        if (isset($data['value'])) {
            $output->setValue((string) $data['value']);
        }

        return $output;
    }

    public function isSpent(): bool
    {
        return $this->spent;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTxIndex(): ?int
    {
        return $this->txIndex;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getValueInSatoshi(): ?string
    {
        return $this->valueInSatoshi;
    }

    public function getN(): ?int
    {
        return $this->n;
    }

    public function getScript(): string
    {
        return $this->script;
    }

    protected function setValue(string $value): void
    {
        $this->valueInSatoshi = $value;
        $this->value = BitcoinConverter::convertToBtc($value);
    }

    private function __construct()
    {
        // nothing to contruct, it was declared so that you cant initialize this class as new BlockchainTransactionOutput()
    }
}
