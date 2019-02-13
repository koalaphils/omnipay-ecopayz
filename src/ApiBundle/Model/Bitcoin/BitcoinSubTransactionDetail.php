<?php

namespace ApiBundle\Model\Bitcoin;

use ApiBundle\Model\PaymentInterface;
use ApiBundle\Model\Transaction;
use DbBundle\Entity\SubTransaction;

class BitcoinSubTransactionDetail implements PaymentInterface
{
    private $details;
    private $transaction;

    public function getDetails(): array
    {
        return $this->details;
    }
    
    public function setBitcoin(?string $bitcoin): self
    {
        $this->details['bitcoin'] = $bitcoin ?? '0';
        
        return $this;
    }
    
    public function getBitcoin(): string
    {
        return $this->details['bitcoin'] ?? '0';
    }

    public function toArray(): array
    {
        return [
            SubTransaction::DETAIL_BITCOIN_REQUESTED_BTC => $this->details['bitcoin'],
        ];
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

}
