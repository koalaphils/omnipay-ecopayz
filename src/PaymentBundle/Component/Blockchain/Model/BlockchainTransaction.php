<?php

namespace PaymentBundle\Component\Blockchain\Model;

class BlockchainTransaction
{
    private $version = 1;
    private $weight;
    private $blockHeight;
    private $relayedBy;
    private $lockTime;
    private $size;
    private $doubleSpend;
    private $time;
    private $txIndex;
    private $hash;
    private $outputs = [];
    private $inputs = [];

    public static function create(array $data): self
    {
        $transaction = new BlockchainTransaction();
        $transaction->version = (int) ($data['ver'] ?? 1);
        $transaction->weight = $data['weight'] ?? null;
        $transaction->blockHeight = $data['block_height'] ?? null;
        $transaction->relayedBy = $data['relayed_by'] ?? null;
        $transaction->lockTime = $data['lock_time'] ?? null;
        $transaction->size = $data['size'] ?? null;
        $transaction->doubleSpend = $data['double_spend'] ?? false;
        $transaction->time = $data['time'] ?? null;
        $transaction->txIndex = $data['tx_index'] ?? null;
        $transaction->hash = $data['hash'] ?? null;

        foreach (($data['out'] ?? []) as $output) {
            $transaction->outputs[] = BlockchainTransactionOutput::create($output);
        }

        foreach (($data['inputs'] ?? []) as $input) {
            $transaction->inputs[] = BlockchainTransactionInput::create($input);
        }

        return $transaction;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getBlockHeight(): ?int
    {
        return $this->blockHeight;
    }

    public function getRelayedBy(): ?string
    {
        return $this->relayedBy;
    }

    public function getLockTime(): ?int
    {
        return $this->lockTime;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function isDoubleSpend(): bool
    {
        return $this->doubleSpend;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function getTxIndex(): ?int
    {
        return $this->txIndex;
    }

    public function getTransactionIndex(): ?int
    {
        return $this->txIndex;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     *
     * @return BlockchainTransactionInput[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function findInputWithAddress(string $address): ?BlockchainTransactionInput
    {
        $inputToBeReturned = null;

        foreach ($this->inputs as $input) {
            if ($input->getPreviousOutAddress() === $address) {
                $inputToBeReturned = $input;
                break;
            }
        }

        return $inputToBeReturned;
    }

    public function findOutputWithN(int $n): ?BlockchainTransactionOutput
    {
        $outputToBeReturned = null;

        foreach ($this->outputs as $output) {
            if ($output->getN() === $n) {
                $outputToBeReturned = $output;
                break;
            }
        }

        return $outputToBeReturned;
    }

    private function __construct()
    {
        // nothing to contruct, it was declared so that you cant initialize this class as new BlockchainTransaction()
    }
}
