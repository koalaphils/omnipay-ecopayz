<?php

namespace CommissionBundle\Request;

use CommissionBundle\Service\CommissionService;

/**
 * Description of CommissionSettingRequest
 *
 * @author cydrick
 */
class CommissionSettingRequest
{
    private $enable;
    private $frequency;
    private $every;
    private $day;
    private $time;
    private $conditions;
    private $startDate;
    private $payoutDay;
    private $dateOfRequest;

    public static function fromArray(array $commissionSetting): CommissionSettingRequest
    {
        $request = new CommissionSettingRequest();
        $request->setEnable(array_get($commissionSetting, 'enable', $request->isEnable()));
        $request->setEvery(array_get($commissionSetting, 'period.every', $request->getEvery()));
        $request->setFrequency(array_get($commissionSetting, 'period.frequency', $request->getFrequency()));
        $request->setDay(array_get($commissionSetting, 'period.day', $request->getDay()));
        $request->setConditions(array_get($commissionSetting, 'conditions', $request->getConditions()));
        $request->setTime(array_get($commissionSetting, 'payout.time', $request->getTime()));
        $request->setPayoutDay(array_get($commissionSetting, 'payout.days', $request->getPayoutDay()));

        if (array_has($commissionSetting, 'startDate')) {
            $request->setStartDate(new \DateTime(array_get($commissionSetting, 'startDate')));
        }

        return $request;
    }

    public function __construct()
    {
        $this->enable = false;
        $this->frequency = CommissionService::SCHEDULER_FREQUENCY_MONTHLY;
        $this->every = 1;
        $this->day = 1;
        $this->conditions = [];
        $this->startDate = new \DateTime('now');
        $this->dateOfRequest = new \DateTime('now');
    }

    public function setEnable(?bool $enable): void
    {
        if (is_null($enable)) {
            $this->enable = false;
        } else {
            $this->enable = $enable;
        }
    }

    public function isEnable(): bool
    {
        if (!is_bool($this->enable)) {
            return false;
        }

        return $this->enable;
    }

    public function setFrequency(string $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getFrequency(): string
    {
        if (!is_string($this->frequency)) {
            return (string) $this->frequency;
        }

        return $this->frequency;
    }

    public function setEvery(string $every): void
    {
        $this->every = $every;
    }

    public function getEvery(): string
    {
        return $this->every;
    }

    public function setDay(?string $day): void
    {
        if (is_null($this->day)) {
            $this->day = '';
        } else {
            $this->day = $day;
        }
    }

    public function getDay(): string
    {
        if (!is_string($this->day)) {
            $this->day = '';
        }

        return $this->day;
    }

    public function getTime(): string
    {
        if (is_null($this->time)) {
            $this->time = '00:00';
        }

        return $this->time;
    }

    public function setTime(string $time): void
    {
        $this->time = $time;
    }

    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function setPayoutDay(string $payoutDay): void
    {
        $this->payoutDay = $payoutDay;
    }

    public function getPayoutDay(): string
    {
        if (is_null($this->payoutDay)) {
            $this->payoutDay = '1';
        }

        return $this->payoutDay;
    }
    
    public function getDateOfRequest(): \DateTimeInterface
    {
        return $this->dateOfRequest;
    }
}
