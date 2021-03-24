<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Transaction;

class BitcoinRateSetting extends Entity implements ActionInterface, TimestampInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $rangeFrom;
    private $rangeTo;
    private $fixedAdjustment;
    private $percentageAdjustment;
    private $isDefault;
    private $type;

    private $lastTouchedBy;

    public function __construct()
    {
        $this->isDefault = false;
    }

    public function setRangeFrom(?string $rangeFrom): BitcoinRateSetting
    {
        $this->rangeFrom = $rangeFrom;

        return $this;
    }

    public function getRangeFrom(): ?string
    {
        return $this->rangeFrom;
    }

    public function setRangeTo(?string $rangeTo): BitcoinRateSetting
    {
        $this->rangeTo = $rangeTo;

        return $this;
    }

    public function getRangeTo(): ?string
    {
        return $this->rangeTo;
    }

    public function setFixedAdjustment(?string $fixedAdjustment): BitcoinRateSetting
    {
        $this->fixedAdjustment = $fixedAdjustment;

        return $this;
    }

    public function getFixedAdjustment(): ?string
    {
        return $this->fixedAdjustment;
    }

    public function setPercentageAdjustment(?string $percentageAdjustment): BitcoinRateSetting
    {
        $this->percentageAdjustment = $percentageAdjustment;

        return $this;
    }

    public function getPercentageAdjustment(): ?string
    {
        return $this->percentageAdjustment;
    }

    public function setIsDefault(bool $isDefault): BitcoinRateSetting
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setType(int $type): BitcoinRateSetting
    {
        $this->type = $type;

        return $this;
    }

    public function setWithdrawalType()
    {
        $this->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);

        return $this;
    }

    public function setDefaultType()
    {
        $this->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);

        return $this;
    }

    public function getType(): int
    {
        return $this->type ?? Transaction::TRANSACTION_TYPE_DEPOSIT;
    }

    public function isWithdrawalType(): bool
    {
        return $this->getType() == Transaction::TRANSACTION_TYPE_WITHDRAW;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    // Audit
    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_BITCOIN_RATE_SETTING;
    }

    public function getIgnoreFields()
    {
        return [];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return sprintf('%s - %s', $this->getRangeFrom(), $this->getRangeTo());
    }

    public function getAuditDetails(): array
    {
        return [
            'rangeFrom' => $this->getRangeFrom(), 
            'rangeTo' => $this->getRangeTo(),
            'fixedAdjustment' => $this->getFixedAdjustment(),
            'percentageAdjustment' => $this->getPercentageAdjustment(),
        ];
    }

    public function getAssociationFields()
    {
        return [];
    }

    public function isAudit()
    {
        return true;
    }

    public function getDateLastTouched()
    {
        return $this->getUpdatedAt() ?? $this->getCreatedAt();
    }

    public function setLastTouchedBy(?User $lastTouchedBy): BitcoinRateSetting
    {
        $this->lastTouchedBy = $lastTouchedBy;

        return $this;
    }

    public function getLastTouchedBy()
    {
        return $this->lastTouchedBy;
    }
}
