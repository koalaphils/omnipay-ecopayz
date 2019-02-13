<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\MetaData;
use AppBundle\ValueObject\Money;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use UnexpectedValueException;

class MemberRunningCommission extends Entity implements ActionInterface, TimestampInterface, AuditInterface
{
    use ActionTrait;
    use TimestampTrait;

    const CONDITION_MET = 'condition_met';
    const CONDITION_UNMET = 'condition_unmet';
    
    const PROCESS_STATUS_NONE = 1;
    const PROCESS_STATUS_COMPUTING = 2;
    const PROCESS_STATUS_COMPUTED = 3;
    const PROCESS_STATUS_COMPUTATION_ERROR = 4;
    const PROCESS_STATUS_PAYING = 5;
    const PROCESS_STATUS_PAID = 6;
    const PROCESS_STATUS_PAY_ERROR = 7;

    private const METADATA_CONVERTIONS = 'convertions';
    private const METADATA_PRECCEEDING_CONVERTIONS = 'precceedingConvertions';
    private const METADATA_CONVERTION_AMOUNT = 'amount';
    private const METADATA_CONVERTION_CONVERTED_AMOUNT = 'convertedAmount';
    private const METADATA_CONVERTION_DESTINATION_CURRENCY = 'destination';
    private const METADATA_ERROR = 'error';

    private $memberProduct;
    private $commission;
    private $runningCommission;
    private $status;
    private $commissionTransaction;
    private $metaData;
    private $commissionPeriod;
    private $preceedingRunningCommission;
    private $succeedingRunningCommission;
    private $processStatus;

    public function __construct()
    {
        $this->commission = 0;
        $this->runningCommission = 0;
        $this->status = self::CONDITION_UNMET;
        $this->metaData = new MetaData([
            'preceedingCommission' => 0,
            self::METADATA_CONVERTIONS => [],
        ]);
        $this->processStatus = self::PROCESS_STATUS_NONE;
    }
    
    public function forRecomputation(): void
    {
        $this->commission = 0;
        $this->setProcessStatusToComputing();
        $this->status = self::CONDITION_UNMET;
        $this->setMetaData($this->getMetaData()->set(self::METADATA_CONVERTIONS, []));
        $this->setPreceedingRunningCommission($this->getPreceedingRunningCommission());
    }

    public static function getStatuses(): array
    {
        return [static::CONDITION_MET, static::CONDITION_UNMET];
    }
    
    public static function getProcessStatuses(): array
    {
        return [
            static::PROCESS_STATUS_NONE,
            static::PROCESS_STATUS_COMPUTING,
            static::PROCESS_STATUS_COMPUTED,
            static::PROCESS_STATUS_COMPUTATION_ERROR,
            static::PROCESS_STATUS_PAYING,
            static::PROCESS_STATUS_PAID,
            static::PROCESS_STATUS_PAY_ERROR,
        ];
    }

    public function getMemberProduct(): MemberProduct
    {
        return $this->memberProduct;
    }

    public function setMemberProduct(MemberProduct $memberProduct): self
    {
        $this->memberProduct = $memberProduct;

        return $this;
    }

    public function getRunningCommission(): string
    {
        return (string) $this->runningCommission;
    }

    public function setRunningCommission(string $runningCommission): self
    {
        $this->runningCommission = $runningCommission;

        return $this;
    }

    public function getRunningCommissionAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->runningCommission);
    }

    public function getTotalCommission(): string
    {
        return Number::add($this->commission, $this->getPreceedingCommission())->toString();
    }

    public function getTotalCommissionAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->getTotalCommission());
    }

    public function getCommission(): string
    {
        return (string) $this->commission;
    }

    public function getCommissionAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->getCommission());
    }

    public function setCommission(string $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function addCommission(string $commission): void
    {
        $this->commission = Number::add($this->commission, $commission)->toString();
        $this->runningCommission = Number::add($this->runningCommission, $commission)->toString();
    }

    public function subtractCommission(string $commission): void
    {
        $this->commission = Number::sub($this->commission, $commission)->toString();
        $this->runningCommission = Number::sub($this->runningCommission, $commission)->toString();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isConditionMet(): bool
    {
        return $this->getStatus() === self::CONDITION_MET;
    }

    public function setStatus(string $status): self
    {
        if (!$this->isValidStatus($status)) {
            throw UnexpectedValueException(sprint('Invalid status %s', $status));
        }

        $this->status = $status;

        return $this;
    }

    public function setStatusToMet(): void
    {
        $this->status = self::CONDITION_MET;
    }

    public function setStatusToUnMet(): void
    {
        $this->status = self::CONDITION_UNMET;
    }

    public function getCommissionPeriod(): ?CommissionPeriod
    {
        return $this->commissionPeriod;
    }

    public function setCommissionPeriod(CommissionPeriod $commissionPeriod): self
    {
        $this->commissionPeriod = $commissionPeriod;

        return $this;
    }
    
    public function getProcessStatus(): int
    {
        return $this->processStatus;
    }
    
    public function setProcessStatus(int $processStatus): self
    {
        if (!$this->isValidProcessStatus($processStatus)) {
            throw UnexpectedValueException(sprint('Invalid status %s', $status));
        }
        
        $this->processStatus = $processStatus;
        
        return $this;
    }
    
    public function setProcessStatusToComputing(): self
    {
        $this->processStatus = self::PROCESS_STATUS_COMPUTING;
        
        return $this;
    }
    
    public function setProcessStatusToComputed(): self
    {
        $this->processStatus = self::PROCESS_STATUS_COMPUTED;
        
        return $this;
    }
    
    public function setProcessStatusToComputationError(): self
    {
        $this->processStatus = self::PROCESS_STATUS_COMPUTATION_ERROR;
        
        return $this;
    }
    
    public function setProcessStatusToPaying(): self
    {
        $this->processStatus = self::PROCESS_STATUS_PAYING;
        
        return $this;
    }
    
    public function setProcessStatusToPaid(): self
    {
        $this->processStatus = self::PROCESS_STATUS_PAID;
        
        return $this;
    }
    
    public function setProcessStatusToPayError(): self
    {
        $this->processStatus = self::PROCESS_STATUS_PAY_ERROR;
        
        return $this;
    }
    
    public function isComputing(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_COMPUTING;
    }
    
    public function isComputed(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_COMPUTED;
    }
    
    public function isComputationHasError(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_COMPUTATION_ERROR;
    }
    
    public function isPaying(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_PAYING;
    }
    
    public function isPaid(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_PAID;
    }
    
    public function isPayingHasError(): bool
    {
        return $this->processStatus === self::PROCESS_STATUS_PAY_ERROR;
    }

    public function getCommissionTransaction(): ?Transaction
    {
        return $this->commissionTransaction;
    }

    public function setCommissionTransaction(?Transaction $commissionTransaction): self
    {
        $this->commissionTransaction = $commissionTransaction;

        return $this;
    }

    public function hasCommissionTransaction(): bool
    {
        return !is_null($this->commissionTransaction);
    }

    public function getMetaData(): MetaData
    {
        return $this->metaData;
    }

    public function setMetaData($metaData): self
    {
        if ($metaData instanceof MetaData) {
            $this->metaData = $metaData;
        } elseif (is_array($metaData)) {
            $this->metaData = new MetaData($metaData);
        } else {
            throw new UnexpectedValueException(sprintf('Metadata must be a type of array or %s', MetaData::class));
        }

        return $this;
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, static::getStatuses());
    }
    
    public function isValidProcessStatus(int $status): bool
    {
        return in_array($status, static::getProcessStatuses());
    }

    public function isCommited(): bool
    {
        return $this->hasCommissionTransaction() || $this->isPaid();
    }

    public function getAssociationFields(): array
    {
        return ['memberProduct', 'commissionTransaction'];
    }

    public function getAuditDetails(): array
    {
        return [
            'status' => $this->getStatus(),
            'commissionPeriod' => [
                'id' => $this->getCommissionPeriod()->getId(),
                'from' => $this->getCommissionPeriod()->getDWLDateFrom()->format('Y-m-d'),
                'to' => $this->getCommissionPeriod()->getDWLDateTo()->format('Y-m-d'),
            ],
            'metaData' => $this->getMetaData()->toArray(),
        ];
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_RUNNING_COMMISSION;
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getIgnoreFields(): array
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getLabel(): string
    {
        return sprintf(
            '%s (%s - %s)',
            $this->getMemberProduct()->getCustomer()->getFullName(),
            $this->getCommissionPeriod()->getDwlDateFrom()->format('Y-m-d'),
            $this->getCommissionPeriod()->getDwlDateTo()->format('Y-m-d')
        );
    }
    
    public function getPreceedingRunningCommission(): ?MemberRunningCommission
    {
        return $this->preceedingRunningCommission;
    }
    
    public function getSucceedingRunningCommission(): ?MemberRunningCommission
    {
        return $this->succeedingRunningCommission;
    }
    
    public function setPreceedingRunningCommission(?MemberRunningCommission $preceedingRunningCommission): self
    {
        $this->preceedingRunningCommission = $preceedingRunningCommission;
        if ($preceedingRunningCommission instanceof MemberRunningCommission) {
            $this->setRunningCommission(Number::add($preceedingRunningCommission->getRunningCommission(), $this->getCommission())->toString());
            $this->setPreceedingCommissionFromRunningCommission($preceedingRunningCommission);
            if ($preceedingRunningCommission->hasSucceedingRunningCommission()
                && $preceedingRunningCommission->getSucceedingRunningCommission()->getId() !== $this->getId()
            ) {
                $this->setSucceedingRunningCommission($preceedingRunningCommission->getSucceedingRunningCommission());
            }
            $preceedingRunningCommission->setSucceedingRunningCommission($this);
        } else {
            $this->setRunningCommission('0');
            $this->setMetaData($this->getMetaData()->remove(self::METADATA_PRECCEEDING_CONVERTIONS));
        }
        
        return $this;
    }
    
    public function setSucceedingRunningCommission(?MemberRunningCommission $succeedingRunningCommission): self
    {
        $this->succeedingRunningCommission = $succeedingRunningCommission;
        
        return $this;
    }
    
    public function hasSucceedingRunningCommission(): bool
    {
        return ($this->getSucceedingRunningCommission() instanceof MemberRunningCommission);
    }

    public function isAudit(): bool
    {
        return $this->isCommited();
    }

    public function getCurrency(): Currency
    {
        return $this->getMemberProduct()->getCurrency();
    }

    public function getPreceedingCommission(): string
    {
        if (!$this->getMetaData()->has('preceedingCommission')) {
            return '0';
        }

        return $this->getMetaData()->get('preceedingCommission');
    }

    public function setPreceedingCommission(string $commission): void
    {
        $this->setMetaData($this->getMetaData()->set('preceedingCommission', $commission));
    }

    public function getCommissionConvertion(): array
    {
        if ($this->getMetaData()->has(self::METADATA_CONVERTIONS)) {
            return $this->getMetaData()->get(self::METADATA_CONVERTIONS);
        }

        return [];
    }

    public function getTotalCommissionConvertion(): array
    {
        $commissionConvertions = $this->getCommissionConvertion();
        $precceedingConvertions = $this->getPrecceedingCommissionConvertion();

        $totalCommissionConvertion = [];
        foreach ($commissionConvertions as $currencyCode => $commissionConvertion) {
            $amount = array_get($totalCommissionConvertion, $currencyCode . '.' . self::METADATA_CONVERTION_AMOUNT, 0);
            $convertedAmount = array_get(
                $totalCommissionConvertion,
                $currencyCode . '.' . self::METADATA_CONVERTION_CONVERTED_AMOUNT,
                0
            );
            $totalCommissionConvertion[$currencyCode] = [
                self::METADATA_CONVERTION_DESTINATION_CURRENCY => $this->getCurrency()->getCode(),
                self::METADATA_CONVERTION_AMOUNT => Number::add(
                    $amount,
                    $commissionConvertion[self::METADATA_CONVERTION_AMOUNT]
                )->toString(),
                self::METADATA_CONVERTION_CONVERTED_AMOUNT => Number::add(
                    $convertedAmount,
                    $commissionConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT]
                )->toString(),
            ];
        }

        foreach ($precceedingConvertions as $currencyCode => $commissionConvertion) {
            $amount = array_get($totalCommissionConvertion, $currencyCode . '.' . self::METADATA_CONVERTION_AMOUNT, 0);
            $convertedAmount = array_get(
                $totalCommissionConvertion,
                $currencyCode . '.' . self::METADATA_CONVERTION_CONVERTED_AMOUNT,
                0
            );
            $totalCommissionConvertion[$currencyCode] = [
                self::METADATA_CONVERTION_DESTINATION_CURRENCY => $this->getCurrency()->getCode(),
                self::METADATA_CONVERTION_AMOUNT => Number::add(
                    $amount,
                    $commissionConvertion[self::METADATA_CONVERTION_AMOUNT]
                )->toString(),
                self::METADATA_CONVERTION_CONVERTED_AMOUNT => Number::add(
                    $convertedAmount,
                    $commissionConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT]
                )->toString(),
            ];
        }

        return $totalCommissionConvertion;
    }

    public function getPrecceedingCommissionConvertion(): array
    {
        if ($this->getMetaData()->has(self::METADATA_PRECCEEDING_CONVERTIONS)) {
            return $this->getMetaData()->get(self::METADATA_PRECCEEDING_CONVERTIONS);
        }

        return [];
    }

    private function setPreceedingCommissionFromRunningCommission(MemberRunningCommission $memberRunningCommission): void
    {
        if ($memberRunningCommission->isConditionMet()) {
            $this->setPreceedingCommission(0);
            $this->setMetaData($this->getMetaData()->remove(self::METADATA_PRECCEEDING_CONVERTIONS));
        } else {
            $precceedingCommission = Number::add(
                $memberRunningCommission->getPreceedingCommission(),
                $memberRunningCommission->getCommission()
            );
            $this->setPreceedingCommission($precceedingCommission);

            $this->setMetaData($this->getMetaData()->set(
                self::METADATA_PRECCEEDING_CONVERTIONS,
                $memberRunningCommission->getTotalCommissionConvertion()
            ));
        }
    }

    public function getPayout(): string
    {
        if ($this->isConditionMet()) {
            return $this->getTotalCommission();
        }

        return '0';
    }

    public function addCommissionConvertion(
        string $sourceCurrencyCode,
        string $sourceAmount,
        string $convertedAmount
    ): void {
        $convertionPath = self::METADATA_CONVERTIONS . '.' . $sourceCurrencyCode;
        $amountPath = $convertionPath . '.' . self::METADATA_CONVERTION_AMOUNT;
        $convertedAmountPath = $convertionPath . '.' . self::METADATA_CONVERTION_CONVERTED_AMOUNT;

        $currentConvertion = [self::METADATA_CONVERTION_AMOUNT => 0, self::METADATA_CONVERTION_CONVERTED_AMOUNT => 0];
        if ($this->getMetaData()->has($amountPath)) {
            $currentConvertion[self::METADATA_CONVERTION_AMOUNT] = $this->getMetaData()->get($amountPath);
        }
        if ($this->getMetaData()->has($convertedAmountPath)) {
            $currentConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT] = $this->getMetaData()->get($convertedAmountPath);
        }

        $computedConvertion = $this->computeConvertion($currentConvertion, $sourceCurrencyCode, $sourceAmount, $convertedAmount);
        $this->setMetaData($this->getMetaData()->set($convertionPath, $computedConvertion));
    }

    private function computeConvertion(
        array $currentConvertion,
        string $sourceCurrencyCode,
        string $sourceAmount,
        string $convertedAmount
    ): array {
        $currentAmount = $currentConvertion[self::METADATA_CONVERTION_AMOUNT];
        $currentConvertedAmount = $currentConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT];

        $computedAmount = Number::add($currentAmount, $sourceAmount)->toString();
        $computedConvertedAmount = Number::add($currentConvertedAmount, $convertedAmount)->toString();

        return [
            self::METADATA_CONVERTION_DESTINATION_CURRENCY => $this->getCurrency()->getCode(),
            self::METADATA_CONVERTION_AMOUNT => $computedAmount,
            self::METADATA_CONVERTION_CONVERTED_AMOUNT => $computedConvertedAmount,
        ];
    }
    
    public function setError(string $error): self
    {
        $this->setMetaData($this->getMetaData()->set(self::METADATA_ERROR, $error));
        
        return $this;
    }
    
    public function removeError(): self
    {
        $this->setMetaData($this->getMetaData()->remove(self::METADATA_ERROR));
        
        return $this;
    }
    
    public function getError(): string
    {
        if (!$this->getMetaData()->has(self::METADATA_ERROR)) {
            return '';
        }
        
        return $this->getMetaData()->get(self::METADATA_ERROR);
    }
    
    public function hasError(): bool
    {
        return $this->isComputationHasError() || $this->isPayingHasError();
    }

    public function getCommissionPeriodId(): int
    {
        return $this->getCommissionPeriod()->getId();
    }
}
