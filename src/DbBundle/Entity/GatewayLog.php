<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Number;
use DbBundle\Entity\Interfaces\GatewayInterface;

class GatewayLog extends Entity
{
    const TYPE_DEPOSIT = 1;
    const TYPE_WITHDRAW = 2;

    private $timestamp;

    private $type;

    private $amount;

    private $balance;

    private $referenceNumber;

    private $details;

    private $currency;

    private $gateway;

    private $paymentOption;

    /**
     * @var string
     */
    private $referenceClass;

    /**
     * @var string
     */
    private $referenceIdentifier;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
        $this->amount = 0;
        $this->balance = 0;
        $this->setDetails([]);
    }

    public function setTimestamp($timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setBalance($balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setAmount($amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setReferenceNumber($referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function setDetails($details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getDetail($key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }

    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency():? Currency
    {
        return $this->currency;
    }

    public function setGateway($gateway): self
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getGateway():? Gateway
    {
        return $this->gateway;
    }

    public function setPaymentOption($paymentOption): self
    {
        $this->paymentOption = $paymentOption;

        return $this;
    }

    public function getPaymentOption():? PaymentOption
    {
        return $this->paymentOption;
    }

    public function isDeposit()
    {
        return $this->getType() == self::TYPE_DEPOSIT;
    }

    public function isWithdraw()
    {
        return $this->getType() == self::TYPE_WITHDRAW;
    }

    public static function translateType($type): string
    {
        switch ($type) {
            case self::TYPE_DEPOSIT:
                $type = 'deposit';
                break;
            case self::TYPE_WITHDRAW:
                $type = 'withdraw';
                break;
        }

        return $type;
    }

    public function getCurrentBalance()
    {
        $previousBalance = new Number($this->getBalance());
        $amount = $this->getAmount();
        $currentBalance = 0;

        if ($this->isDeposit()) {
            $currentBalance = $previousBalance->plus($amount);
        } elseif ($this->isWithdraw()) {
            $currentBalance = $previousBalance->minus($amount);
        }

        return $currentBalance->toFloat();
    }

    public static function translateOperationToType($operation)
    {
        if ($operation == GatewayInterface::OPERATION_ADD) {
            $type = self::TYPE_DEPOSIT;
        } elseif ($operation == GatewayInterface::OPERATION_SUB) {
            $type = self::TYPE_WITHDRAW;
        }

        return $type;
    }

    public function getOrigin():? string
    {
        $origin = '';
        $referenceClass = $this->getDetail('reference_class');

        switch ($referenceClass) {
            case Transaction::class:
                $origin = 'transaction';
                break;
            case GatewayTransaction::class:
                $origin = 'gatewayTransaction';
                break;
            case Gateway::class:
                $origin = 'gateway';
                break;
        }

        return $origin;
    }

    public function getReferenceIdentifier(): ?string
    {
        return $this->referenceIdentifier;
    }

    public function setReferenceIdentifier(?string $referenceIdentifier): self
    {
        $this->referenceIdentifier = $referenceIdentifier;

        return $this;
    }

    public function getReferenceClass(): ?string
    {
        return $this->referenceClass;
    }

    public function setReferenceClass(?string $referenceClass): self
    {
        $this->referenceClass = $referenceClass;

        return $this;
    }
}
