<?php

namespace ApiBundle\Model\Bitcoin;

use ApiBundle\Model\PaymentInterface;
use ApiBundle\Model\SubTransaction;
use ApiBundle\Model\Transaction as ApiTransaction;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Transaction;

class BitcoinPayment implements PaymentInterface
{
    private $details;
    private $transaction;

    public function setBlockchainRate(?string $blockchainRate): self
    {
        $this->details['blockchainRate'] = $blockchainRate ?? '0';

        return $this;
    }

    public function getBlockchainRate(): string
    {
        return $this->details['blockchainRate'] ?? '0';
    }

    public function setRate(?string $rate): self
    {
        $this->details['rate'] = $rate ?? '0';

        return $this;
    }

    public function getRate(): string
    {
        return $this->details['rate'] ?? '';
    }

    public function setRateDetails(?BitcoinRateDetail $rateDetails): self
    {
        $this->details['rateDetails'] = $rateDetails ?? new BitcoinRateDetail();

        return $this;
    }

    public function getRateDetails(): ?BitcoinRateDetail
    {
        if (is_null($this->details['rateDetails'] ?? null)) {
            $this->details['rateDetails'] = new BitcoinRateDetail();
        }

        return $this->details['rateDetails'];
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toArray(): array
    {
        return [
            Transaction::DETAIL_BITCOIN_BLOCKCHAIN_RATE => $this->getBlockchainRate(),
            Transaction::DETAIL_BITCOIN_RATE => $this->getRate(),
            Transaction::DETAIL_BITCOIN_RATE_DETAIL => $this->getRateDetails()->toArray()
        ];
    }

    public function getTransaction(): ?ApiTransaction
    {
        return $this->transaction;
    }

    public function setTransaction(?ApiTransaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getTotalBitcoin(): string
    {
        $total = new Number('0');
        foreach ($this->getTransaction()->getSubTransactions() as $subTransaction) {
            /* @var $subTransaction SubTransaction */
            if ($subTransaction->getPaymentDetails() instanceof BitcoinSubTransactionDetail) {
                if (Number::isNumber($subTransaction->getPaymentDetails()->getBitcoin())) {     
                    $total = $total->plus($subTransaction->getPaymentDetails()->getBitcoin());
                }
            }
        }

        return $total->toString();
    }
}
