<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta\Bitcoin;

use ApiBundle\Request\Transaction\DepositRequest;
use ApiBundle\Request\Transaction\Meta\PaymentInterface;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class BitcoinPayment implements  PaymentInterface, GroupSequenceProviderInterface
{
    /**
     * @var string
     */
    private $blockchainRate;

    /**
     * @var string
     */
    private $rate;

    /**
     * @var BitcoinRateDetail
     */
    private $rateDetail;

    private $transactionType;

    public static function createFromArray(array $data, string $transactionType): self
    {
        $instance = new static();
        $instance->blockchainRate = $data['blockchain_rate'] ?? '';
        $instance->rate = $data['rate'] ?? '';
        $instance->rateDetail = BitcoinRateDetail::createFromArray($data['rate_detail'] ?? []);
        $instance->transactionType = $transactionType;

        return $instance;
    }

    public function getBlockchainRate(): string
    {
        return $this->blockchainRate;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getRateDetail(): BitcoinRateDetail
    {
        return $this->rateDetail;
    }

    public function toArray(): array
    {
        return [
            'blockchain_rate' => $this->blockchainRate,
            'rate' => $this->rate,
            'rate_detail' => $this->rateDetail->toArray(),
        ];
    }

    public function getGroupSequence()
    {
        return [['BitcoinPayment', $this->transactionType], 'afterBlank', 'correctRate'];
    }
}