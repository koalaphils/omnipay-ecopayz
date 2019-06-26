<?php

namespace PaymentBundle\Model\Bitcoin;

class SettingModel
{
    const BITCOIN_ENABLE_AUTO_DECLINE = false;
    const BITCOIN_DEFAULT_MIN = 20;
    const BITCOIN_SETTING = 'setting';
    const BITCOIN_DEPOSIT_CONFIGURATION = 'configuration';
    const BITCOIN_WITHDRAWAL_CONFIGURATION = 'withdrawalConfiguration';
    const BITCOIN_TIME_DURATION_NAME = 'minutes';
    const BITCOIN_MINIMUM_ALLOWED_DEPOSIT = '0.0000000001';
    const BITCOIN_MAXIMUM_ALLOWED_DEPOSIT = 9;
    const BITCOIN_LOCK_PERIOD_SETTING = 'lockRatePeriodSetting';
    const BITCOIN_MINIMUM_ALLOWED_WITHDRAWAL = '0.0000000001';
    const BITCOIN_MAXIMUM_ALLOWED_WITHDRAWAL = 3000;
    
    protected $autoDecline;
    protected $minutesInterval;
    protected $minimumAllowedDeposit;
    protected $maximumAllowedDeposit;
    protected $autoLock;
    protected $minutesLockDownInterval;
    protected $minimumAllowedWithdrawal;
    protected $maximumAllowedWithdrawal;
    protected $status;

    public function getAutoDecline(): bool
    {
        return $this->autoDecline ?? false;
    }

    public function setAutoDecline(bool $autoDecline): self
    {
        $this->autoDecline = $autoDecline;

        return $this;
    }

    public function getMinutesInterval(): ?int
    {
        return $this->minutesInterval;
    }

    public function setMinutesInterval(int $minutesInterval): self
    {
        $this->minutesInterval = $minutesInterval;

        return $this;
    }

    public function getMinimumAllowedDeposit(): ?string
    {
        return $this->minimumAllowedDeposit;
    }

    public function setMinimumAllowedDeposit(string $minimumAllowedDeposit): self
    {
        $this->minimumAllowedDeposit = $minimumAllowedDeposit;

        return $this;
    }

    public function getMaximumAllowedDeposit(): ?string
    {
        return $this->maximumAllowedDeposit;
    }

    public function setMaximumAllowedDeposit(string $maximumAllowedDeposit): self
    {
        $this->maximumAllowedDeposit = $maximumAllowedDeposit;

        return $this;
    }

    public function getMinimumAllowedWithdrawal(): ?string
    {
        return $this->minimumAllowedWithdrawal;
    }

    public function setMinimumAllowedWithdrawal(string $minimumAllowedWithdrawal): self
    {
        $this->minimumAllowedWithdrawal = $minimumAllowedWithdrawal;

        return $this;
    }

    public function getMaximumAllowedWithdrawal(): ?string
    {
        return $this->maximumAllowedWithdrawal;
    }

    public function setMaximumAllowedWithdrawal(string $maximumAllowedWithdrawal): self
    {
        $this->maximumAllowedWithdrawal = $maximumAllowedWithdrawal;

        return $this;
    }

    public function getAutoLock(): bool
    {
        return $this->autoLock ?? false;
    }

    public function setAutoLock(bool $autoLock): self
    {
        $this->autoLock = $autoLock;

        return $this;
    }

    public function getMinutesLockDownInterval(): ?int
    {
        return $this->minutesLockDownInterval;
    }

    public function setMinutesLockDownInterval(int $minutesLockDownInterval): self
    {
        $this->minutesLockDownInterval = $minutesLockDownInterval;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
