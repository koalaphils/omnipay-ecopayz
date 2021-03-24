<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Number;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\GatewayInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionInterface;

class GatewayTransaction extends Entity implements ActionInterface, TimestampInterface, GatewayInterface, AuditInterface, VersionInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const GATEWAY_TRANSACTION_TYPE_DEPOSIT = 1;
    const GATEWAY_TRANSACTION_TYPE_WITHDRAW = 2;
    const GATEWAY_TRANSACTION_TYPE_TRANSFER = 3;

    const GATEWAY_TRANSACTION_STATUS_PENDING = 1;
    const GATEWAY_TRANSACTION_STATUS_APPROVED = 2;
    const GATEWAY_TRANSACTION_STATUS_VOIDED = 3; //NOTE: This will differentiate filters to add isVioded() (true only) and not on actual data in status column

    private $number;

    private $type;

    private $date;

    private $amount;

    private $amountTo;

    private $fees;

    private $netAmount;

    private $netAmountTo;

    private $status;

    private $details;

    private $isVoided;

    private $paymentOption;

    private $currency;

    private $gateway;

    private $gatewayTo;

    public function __construct()
    {
        $this->amount = 0;
        $this->amountTo = 0;
        $this->netAmount = 0;
        $this->netAmountTo = 0;
        $this->isVoided = false;
        $this->date = new \DateTime();
        $this->status = self::GATEWAY_TRANSACTION_STATUS_PENDING;
        $this->setFees([
            'fee' => 0,
            'feeTo' => 0
        ]);
        $this->setDetails([]);
    }

    public function setNumber($number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
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

    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
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

    public function setAmountTo($amountTo): self
    {
        $this->amountTo = $amountTo;

        return $this;
    }

    public function getAmountTo(): float
    {
        return $this->amountTo;
    }

    public function setNetAmount($netAmount): self
    {
        $this->netAmount = $netAmount;

        return $this;
    }

    public function getNetAmount():? float
    {
        return $this->netAmount;
    }

    public function setNetAmountTo($netAmountTo): self
    {
        $this->netAmountTo = $netAmountTo;

        return $this;
    }

    public function getNetAmountTo():? float
    {
        return $this->netAmountTo;
    }

    public function setFees($fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    public function getFees(): array
    {
        return $this->fees;
    }

    public function setFee($key, $fee)
    {
        array_set($this->fees, $key, $fee);

        return $this;
    }

    public function getFee($key, $default = 0)
    {
        return array_get($this->fees, $key, $default);
    }

    public function hasFee($key)
    {
        return array_has($this->fees, $key);
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
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

    public function setIsVoided($isVoided): self
    {
        $this->isVoided = $isVoided;

        return $this;
    }

    public function getIsVoided(): bool
    {
        return $this->isVoided;
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

    public function setGatewayTo($gatewayTo): self
    {
        $this->gatewayTo = $gatewayTo;

        return $this;
    }

    public function getGatewayTo():? Gateway
    {
        return $this->gatewayTo;
    }

    public static function getTypeAsTexts(): array
    {
        return [
            self::GATEWAY_TRANSACTION_TYPE_DEPOSIT => 'deposit',
            self::GATEWAY_TRANSACTION_TYPE_WITHDRAW => 'withdraw',
            self::GATEWAY_TRANSACTION_TYPE_TRANSFER => 'transfer',
        ];
    }

    public function getTypeText(): string
    {
        return self::translateType($this->getType(), true);
    }

    public static function translateType($type, $isInteger = false)
    {
        if ($isInteger == false) {
            switch ($type) {
                case 'deposit':
                    $type = self::GATEWAY_TRANSACTION_TYPE_DEPOSIT;
                    break;
                case 'withdraw':
                    $type = self::GATEWAY_TRANSACTION_TYPE_WITHDRAW;
                    break;
                case 'transfer':
                    $type = self::GATEWAY_TRANSACTION_TYPE_TRANSFER;
                    break;
            }
        } else {
            switch ($type) {
                case self::GATEWAY_TRANSACTION_TYPE_DEPOSIT:
                    $type = 'deposit';
                    break;
                case self::GATEWAY_TRANSACTION_TYPE_WITHDRAW:
                    $type = 'withdraw';
                    break;
                case self::GATEWAY_TRANSACTION_TYPE_TRANSFER:
                    $type = 'transfer';
                    break;
            }
        }

        return $type;
    }

    public function isTransfer(): bool
    {
        return $this->getType() == self::GATEWAY_TRANSACTION_TYPE_TRANSFER;
    }

    public function isDeposit(): bool
    {
        return $this->getType() == self::GATEWAY_TRANSACTION_TYPE_DEPOSIT;
    }

    public function isWithdraw(): bool
    {
        return $this->getType() == self::GATEWAY_TRANSACTION_TYPE_WITHDRAW;
    }

    public static function getStatusAsTexts(): array
    {
        return [
            self::GATEWAY_TRANSACTION_STATUS_PENDING => 'pending',
            self::GATEWAY_TRANSACTION_STATUS_APPROVED => 'approved',
        ];
    }

    public function setStatusAs($isPending = false, $isApproved = false, $isVoid = false)
    {
        if ($isPending) {
            $this->status = self::GATEWAY_TRANSACTION_STATUS_PENDING;
        } elseif ($isApproved) {
            $this->status = self::GATEWAY_TRANSACTION_STATUS_APPROVED;
        } elseif ($isVoid) {
            $this->isVoided = true;
        }
    }

    public function isApproved(): bool
    {
        return $this->getStatus() == self::GATEWAY_TRANSACTION_STATUS_APPROVED && !$this->isVoided();
    }

    public function isPending(): bool
    {
        return $this->getStatus() == self::GATEWAY_TRANSACTION_STATUS_PENDING && !$this->isVoided();
    }

    public function getAccount(): Gateway
    {
        return $this->getGateway();
    }

    public function getAccountTo():? Gateway
    {
        if ($this->isTransfer()) {
            return $this->getGatewayTo();
        }

        return null;
    }

    public function getFinalAmount($to = false): float
    {
        $amount = new Number($this->getAmount());
        $fee = $this->getFees()['fee'];
        $finalAmount = 0;

        if ($this->isDeposit()) {
            $finalAmount = $amount->minus($fee);
        } elseif ($this->isWithdraw()) {
            $finalAmount = $amount->plus($fee);
        } elseif ($this->isTransfer()) {
            if ($to) {
                $amountTo = new Number($this->getAmountTo());
                $feeTo = $this->getFees()['feeTo'];
                $finalAmount = $amountTo->minus($feeTo);
            } else {
                $finalAmount = $amount->plus($fee);
            }
        }

        return $finalAmount->toFloat();
    }

    public function processGateway(): bool
    {
        return $this->isApproved() || $this->isVoided();
    }

    public function isVoided(): bool
    {
        return $this->isVoided;
    }

    public function translateStatus(): string
    {
        switch ($this->getStatus()) {
            case self::GATEWAY_TRANSACTION_STATUS_PENDING:
                $label = 'Pending';
                break;
            case self::GATEWAY_TRANSACTION_STATUS_APPROVED:
                $label = 'Approved';
                break;
        }

        if ($this->isVoided()) {
            $label = 'Void';
        }

        return $label;
    }

    public function getOperation($to = false): string
    {
        if ($this->isDeposit()) {
            if ($this->isApproved()) {
                $operation = self::OPERATION_ADD;
            } elseif ($this->isVoided()) {
                $operation = self::OPERATION_SUB;
            }
        } elseif ($this->isWithdraw()) {
            if ($this->isApproved()) {
                $operation = self::OPERATION_SUB;
            } elseif ($this->isVoided()) {
                $operation = self::OPERATION_ADD;
            }
        } elseif ($this->isTransfer()) {
            if ($this->isApproved()) {
                $operation = $to ? self::OPERATION_ADD : self::OPERATION_SUB;
            } elseif ($this->isVoided()) {
                $operation = $to ? self::OPERATION_SUB : self::OPERATION_ADD;
            }
        }

        return $operation;
    }

    public function validateGateways()
    {
        if ($this->isTransfer() && $this->getGateway()->getId() === $this->getGatewayTo()->getId()) {
            return false;
        }

        return true;
    }

    public function getReferenceNumber()
    {
        return $this->getNumber();
    }

    public function getTransactionDetails(): array
    {
        return [
          'type' => $this->getType(),
        ];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_GATEWAY_TRANSACTION;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'netAmount'];
    }

    public function getAssociationFields()
    {
        return ['paymentOption', 'currency', 'gateway', 'gatewayTo'];
    }

    public function getLabel()
    {
        return $this->getNumber();
    }

    public function isAudit()
    {
        return true;
    }

    public function getGatewayCurrency():? Currency
    {
        return $this->getCurrency();
    }

    public function getGatewayPaymentOption():? PaymentOption
    {
        return $this->getPaymentOption();
    }

    public function getVersionColumn()
    {
        return 'updatedAt';
    }

    public function getVersionType()
    {
        return 'datetime';
    }

    public function getAuditDetails(): array
    {
        return [
            'number' => $this->getNumber(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'amount' => $this->getAmount(),
            'amountTo' => $this->getAmountTo(),
            'status' => $this->getStatus(),
            'isVoided' => $this->getIsVoided(),
            'gatewayTo' => $this->getGatewayTo(),
            'gateway' => $this->getGateway(),
            'currency' => $this->getCurrency(),
            'paymentOption' => $this->getPaymentOption(),
        ];
    }
}
