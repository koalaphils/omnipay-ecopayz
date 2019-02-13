<?php

namespace PaymentBundle\Component\Blockchain\Model;

use PaymentBundle\Component\Blockchain\BitcoinConverter;

class BlockchainTransactionInput
{
    private $sequence;
    private $witness;
    private $script;
    private $coinBase = true;

    private $prevOutSpent;
    private $prevOutTxIndex;
    private $prevOutType;
    private $prevOutAddress;
    private $prevOutValue;
    private $prevOutValueInSatoshi;
    private $prevOutN;
    private $prevOutScript;

    public static function create(array $data): self
    {
        $input = new BlockchainTransactionInput();
        $input->sequence = $data['sequence'] ?? null;
        $input->witness = $data['witness'] ?? null;
        $input->script = $data['script'] ?? null;
        if (array_key_exists('prev_out', $data)) {
            $input->coinBase = false;
            $prevOut = $data['prev_out'];
            $input->prevOutSpent = $prevOut['spent'] ?? false;
            $input->prevOutTxIndex = $prevOut['tx_index'] ?? null;
            $input->prevOutType = $prevOut['type'] ?? null;
            $input->prevOutAddress = $prevOut['addr'] ?? null;
            if (array_key_exists('value', $prevOut)) {
                $input->setValue($prevOut['value']);
            }
            $input->prevOutN = $prevOut['n'] ?? null;
            $input->prevOutScript = $prevOut['script'] ?? null;
        }

        return $input;
    }

    public function getWitness(): ?string
    {
        return $this->witness;
    }

    public function getPreviousOutAddress(): ?string
    {
        return $this->prevOutAddress;
    }

    public function getPreviousOutN(): ?int
    {
        return $this->prevOutN;
    }

    protected function setValue(string $value): void
    {
        $this->prevOutValueInSatoshi = $value;
        $this->prevOutValue = BitcoinConverter::convertToBtc($value);
    }

    private function __construct()
    {
        // nothing to contruct, it was declared so that you cant initialize this class as new BlockchainTransactionInput()
    }
}
