<?php

namespace DbBundle\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use UnexpectedValueException;

/**
 * Description of CommissionSchedule
 *
 * @author cydrick
 */
class CommissionPeriod extends Entity implements ActionInterface, TimestampInterface
{
    use ActionTrait;
    use TimestampTrait;
    
    const STATUS_NOT_YET_COMPUTED = 1;
    const STATUS_COMPUTING = 2;
    const STATUS_SUCCESSFULL_COMPUTATION = 3;
    const STATUS_FAILED_COMPUTATION = 4;
    const STATUS_EXECUTING_PAYOUT = 5;
    const STATUS_SUCCESSFULL_PAYOUT = 6;
    const STATUS_FAILED_PAYOUT = 7;

    private $dwlDateFrom;
    private $dwlDateTo;
    private $payoutAt;
    private $status;
    private $details;
    private $conditions;

    public function __construct()
    {
        $this->executed = false;
        $this->conditions = [];
        $this->details = [];
        $this->status = self::STATUS_NOT_YET_COMPUTED;
    }

    public function getDWLDateFrom(): DateTimeImmutable
    {
        if ($this->dwlDateFrom instanceof DateTime) {
            $this->dwlDateFrom = DateTimeImmutable::createFromMutable($this->dwlDateFrom);
        }

        return $this->dwlDateFrom;
    }

    public function setDWLDateFrom(DateTimeInterface $dwlDateFrom): self
    {
        if ($dwlDateFrom instanceof DateTimeImmutable) {
            $this->dwlDateFrom = $dwlDateFrom;
        } elseif ($dwlDateFrom instanceof DateTime) {
            $this->dwlDateFrom = DateTimeImmutable::createFromMutable($dwlDateFrom);
        } else {
            throw new UnexpectedValueException(sprintf(
                'DWL Date From must be type of %s or %s only.',
                DateTime::class,
                DateTimeImmutable::class
            ));
        }

        return $this;
    }

    public function getDWLDateTo(): DateTimeImmutable
    {
        if ($this->dwlDateTo instanceof DateTime) {
            $this->dwlDateTo = DateTimeImmutable::createFromMutable($this->dwlDateTo);
        }

        return $this->dwlDateTo;
    }

    public function setDWLDateTo(DateTimeInterface $dwlDateTo): self
    {
        if ($dwlDateTo instanceof DateTimeImmutable) {
            $this->dwlDateTo = $dwlDateTo;
        } elseif ($dwlDateTo instanceof DateTime) {
            $this->dwlDateTo = DateTimeImmutable::createFromMutable($dwlDateTo);
        } else {
            throw new UnexpectedValueException(sprintf(
                'DWL Date To must be type of %s or %s only.',
                DateTime::class,
                DateTimeImmutable::class
            ));
        }

        return $this;
    }

    public function getPayoutAt(): DateTimeImmutable
    {
        if ($this->payoutAt instanceof DateTime) {
            $this->payoutAt = DateTimeImmutable::createFromMutable($this->payoutAt);
        }
        
        return $this->payoutAt;
    }

    public function setPayoutAt(DateTimeInterface $payoutAt): self
    {
        if ($payoutAt instanceof DateTimeImmutable) {
            $this->payoutAt = $payoutAt;
        } elseif ($payoutAt instanceof DateTime) {
            $this->payoutAt = DateTimeImmutable::createFromMutable($payoutAt);
        } else {
            throw new UnexpectedValueException(sprintf(
                'Scheduled At must be type of %s or %s only.',
                DateTime::class,
                DateTimeImmutable::class
            ));
        }

        return $this;
    }
    
    public function setStatus(int $status): self
    {
        $this->status = $status;
        
        return $this;
    }
    
    public function getStatus(): int
    {
        return $this->status;
    }
    
    public function isNotYetComputed(): bool
    {
        return $this->status === self::STATUS_NOT_YET_COMPUTED;
    }
    
    public function isComputing(): bool
    {
        return $this->status === self::STATUS_COMPUTING;
    }
    
    public function isSuccessfullComputation(): bool
    {
        return $this->status === self::STATUS_SUCCESSFULL_COMPUTATION;
    }
    
    public function isFailedComputation(): bool
    {
        return $this->status === self::STATUS_FAILED_COMPUTATION;
    }
    
    public function isExecutingPayout(): bool
    {
        return $this->status === self::STATUS_EXECUTING_PAYOUT;
    }
    
    public function isSuccessfullPayout(): bool
    {
        return $this->status === self::STATUS_SUCCESSFULL_PAYOUT;
    }
    
    public function isFailedPayout(): bool
    {
        return $this->status === self::STATUS_FAILED_PAYOUT;
    }
    
    public function setToComputing(): void
    {
        $this->status = self::STATUS_COMPUTING;
    }
    
    public function setToSuccessfullComputation(): void
    {
        $this->status = self::STATUS_SUCCESSFULL_COMPUTATION;
    }
    
    public function setToFailedComputation(): void
    {
        $this->status = self::STATUS_FAILED_COMPUTATION;
    }
    
    public function setToExecutingPayout(): void
    {
        $this->status = self::STATUS_EXECUTING_PAYOUT;
    }
    
    public function setToSuccessfullPayout(): void
    {
        $this->status = self::STATUS_SUCCESSFULL_PAYOUT;
    }
    
    public function setToFailedPayout(): void
    {
        $this->status = self::STATUS_FAILED_PAYOUT;
    }
    
    public function getDetails(): array
    {
        return $this->details;
    }
    
    public function setDetails(array $details): self
    {
        $this->details = $details;
        
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions): self
    {
        $this->conditions = $conditions;

        return $this;
    }
}
