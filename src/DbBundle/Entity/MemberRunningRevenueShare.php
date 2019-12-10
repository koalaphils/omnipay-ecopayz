<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\MetaData;
use AppBundle\ValueObject\Money;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use UnexpectedValueException;

class MemberRunningRevenueShare extends Entity implements ActionInterface, TimestampInterface, AuditInterface
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

    const MIN_PAYOUT = 100;

    private const METADATA_CONVERTIONS = 'convertions';
    private const METADATA_PRECCEEDING_CONVERTIONS = 'precceedingConvertions';
    private const METADATA_CONVERTION_AMOUNT = 'amount';
    private const METADATA_CONVERTION_CONVERTED_AMOUNT = 'convertedAmount';
    private const METADATA_CONVERTION_DESTINATION_CURRENCY = 'destination';
    private const METADATA_ERROR = 'error';

    private $member;
    private $revenueShare;
    private $runningRevenueShare;
    private $status;
    private $revenueShareTransaction;
    private $metaData;
    private $revenueSharePeriod;
    private $precedingRunningRevenueShare;
    private $succeedingRunningRevenueShare;
    private $processStatus;

    public function __construct()
    {
        $this->revenueShare = 0;
        $this->runningRevenueShare = 0;
        $this->status = self::CONDITION_UNMET;
        $this->metaData = new MetaData([
            'precedingRevenueShare' => 0,
            self::METADATA_CONVERTIONS => [],
        ]);
        $this->processStatus = self::PROCESS_STATUS_NONE;
    }
    
    public function forRecomputation(): void
    {
        $this->revenueShare = 0;
        $this->setProcessStatusToComputing();
        $this->status = self::CONDITION_UNMET;
        $this->setMetaData($this->getMetaData()->set(self::METADATA_CONVERTIONS, []));
        $this->setPrecedingRunningRevenueShare($this->getPrecedingRunningRevenueShare());
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

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setMember(Member $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function getRunningRevenueShare(): string
    {
        return (string) $this->runningRevenueShare;
    }

    public function setRunningRevenueShare(string $runningRevenueShare): self
    {
        $this->runningRevenueShare = $runningRevenueShare;

        return $this;
    }

    public function getRunningRevenueShareAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->runningRevenueShare);
    }

    public function getTotalRevenueShare(): string
    {
        return Number::add($this->revenueShare, $this->getPrecedingRevenueShare())->toString();
    }

    public function getTotalRevenueShareAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->getTotalRevenueShare());
    }

    public function getRevenueShare(): string
    {
        return (string) $this->revenueShare;
    }

    public function getRevenueShareAsMoney(): Money
    {
        return new Money($this->getCurrency(), $this->getRevenueShare());
    }

    public function setRevenueShare(string $revenueShare): self
    {
        $this->revenueShare = $revenueShare;

        return $this;
    }

    public function addRevenueShare(string $revenueShare): void
    {
        $this->revenueShare = Number::add($this->revenueShare, $revenueShare)->toString();
        $this->runningRevenueShare = Number::add($this->runningRevenueShare, $revenueShare)->toString();
    }

    public function subtractRevenueShare(string $revenueShare): void
    {
        $this->revenueShare = Number::sub($this->revenueShare, $revenueShare)->toString();
        $this->runningRevenueShare = Number::sub($this->runninRevenueShare, $revenueShare)->toString();
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

    public function getRevenueSharePeriod(): ?CommissionPeriod
    {
        return $this->revenueSharePeriod;
    }

    public function setRevenueSharePeriod(CommissionPeriod $revenueSharePeriod): self
    {
        $this->revenueSharePeriod = $revenueSharePeriod;

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

    public function getRevenueShareTransaction(): ?Transaction
    {
        return $this->revenueShareTransaction;
    }

    public function setRevenueShareTransaction(?Transaction $revenueShareTransaction): self
    {
        $this->revenueShareTransaction = $revenueShareTransaction;

        return $this;
    }

    public function hasRevenueShareTransaction(): bool
    {
        return !is_null($this->revenueShareTransaction);
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
        return $this->hasRevenueShareTransaction() || $this->isPaid();
    }

    public function getAssociationFields(): array
    {
        return ['memberProduct', 'revenueShareTransaction'];
    }

    public function getAuditDetails(): array
    {
        return [
            'status' => $this->getStatus(),
            'commissionPeriod' => [
                'id' => $this->getRevenueSharePeriod()->getId(),
                'from' => $this->getRevenueSharePeriod()->getDWLDateFrom()->format('Y-m-d'),
                'to' => $this->getRevenueSharePeriod()->getDWLDateTo()->format('Y-m-d'),
            ],
            'metaData' => $this->getMetaData()->toArray(),
        ];
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_RUNNING_REVENUE_SHARE;
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
            $this->getRevenueSharePeriod()->getDwlDateFrom()->format('Y-m-d'),
            $this->getRevenueSharePeriod()->getDwlDateTo()->format('Y-m-d')
        );
    }
    
    public function getPrecedingRunningRevenueShare(): ?MemberRunningRevenueShare
    {
        return $this->precedingRunningRevenueShare;
    }
    
    public function getSucceedingRunningRevenueShare(): ?MemberRunningRevenueShare
    {
        return $this->succeedingRunningRevenueShare;
    }

    public function setPrecedingRunningRevenueShare(?MemberRunningRevenueShare $precedingRunningRevenueShare): self
    {
        $this->precedingRunningRevenueShare = $precedingRunningRevenueShare;
        if ($precedingRunningRevenueShare instanceof MemberRunningRevenueShare) {
            $this->setRunningRevenueShare(Number::add($precedingRunningRevenueShare->getRunningRevenueShare(), $this->getRevenueShare())->toString());
            $this->setPrecedingRevenueShareFromRunningRevenueShare($precedingRunningRevenueShare);
            if ($precedingRunningRevenueShare->hasSucceedingRunningRevenueShare()
                && $precedingRunningRevenueShare->getSucceedingRunningRevenueShare()->getId() !== $this->getId()
            ) {
                $this->setSucceedingRunningRevenueShare($precedingRunningRevenueShare->getSucceedingRunningRevenueShare());
            }
            $precedingRunningRevenueShare->setSucceedingRunningRevenueShare($this);
        } else {
            $this->setRunningRevenueShare('0');
            $this->setMetaData($this->getMetaData()->remove(self::METADATA_PRECCEEDING_CONVERTIONS));
        }
        
        return $this;
    }
    
    public function setSucceedingRunningRevenueShare(?MemberRunningRevenueShare $succeedingRunningRevenueShare): self
    {
        $this->succeedingRunningRevenueShare = $succeedingRunningRevenueShare;
        
        return $this;
    }
    
    public function hasSucceedingRunningRevenueShare(): bool
    {
        return ($this->getSucceedingRunningRevenueShare() instanceof MemberRunningRevenueShare);
    }

    public function isAudit(): bool
    {
        return $this->isCommited();
    }

    public function getCurrency(): Currency
    {
        return $this->getMemberProduct()->getCurrency();
    }

    public function getPrecedingRevenueShare(): string
    {
        if (!$this->getMetaData()->has('precedingRevenueShare')) {
            return '0';
        }

        return $this->getMetaData()->get('precedingRevenueShare');
    }

    public function setPrecedingRevenueShare(string $revenueShare): void
    {
        $this->setMetaData($this->getMetaData()->set('precedingRevenueShare', $revenueShare));
    }

    public function getRevenueShareConvertion(): array
    {
        if ($this->getMetaData()->has(self::METADATA_CONVERTIONS)) {
            return $this->getMetaData()->get(self::METADATA_CONVERTIONS);
        }

        return [];
    }

    public function getTotalRevenueShareConvertion(): array
    {
        $revenueShareConvertions = $this->getRevenueShareConvertion();
        $precceedingConvertions = $this->getPrecceedingRevenueShareConvertion();

        $totalRevenueShareConvertion = [];
        foreach ($revenueShareConvertions as $currencyCode => $revenueShareConvertion) {
            $amount = array_get($totalRevenueShareConvertion, $currencyCode . '.' . self::METADATA_CONVERTION_AMOUNT, 0);
            $convertedAmount = array_get(
                $totalRevenueShareConvertion,
                $currencyCode . '.' . self::METADATA_CONVERTION_CONVERTED_AMOUNT,
                0
            );
            $totalRevenueShareConvertion[$currencyCode] = [
                self::METADATA_CONVERTION_DESTINATION_CURRENCY => $this->getCurrency()->getCode(),
                self::METADATA_CONVERTION_AMOUNT => Number::add(
                    $amount,
                    $revenueShareConvertion[self::METADATA_CONVERTION_AMOUNT]
                )->toString(),
                self::METADATA_CONVERTION_CONVERTED_AMOUNT => Number::add(
                    $convertedAmount,
                    $revenueShareConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT]
                )->toString(),
            ];
        }

        foreach ($precceedingConvertions as $currencyCode => $revenueShareConvertion) {
            $amount = array_get($totalRevenueShareConvertion, $currencyCode . '.' . self::METADATA_CONVERTION_AMOUNT, 0);
            $convertedAmount = array_get(
                $totalRevenueShareConvertion,
                $currencyCode . '.' . self::METADATA_CONVERTION_CONVERTED_AMOUNT,
                0
            );
            $totalRevenueShareConvertion[$currencyCode] = [
                self::METADATA_CONVERTION_DESTINATION_CURRENCY => $this->getCurrency()->getCode(),
                self::METADATA_CONVERTION_AMOUNT => Number::add(
                    $amount,
                    $revenueShareConvertion[self::METADATA_CONVERTION_AMOUNT]
                )->toString(),
                self::METADATA_CONVERTION_CONVERTED_AMOUNT => Number::add(
                    $convertedAmount,
                    $revenueShareConvertion[self::METADATA_CONVERTION_CONVERTED_AMOUNT]
                )->toString(),
            ];
        }

        return $totalRevenueShareConvertion;
    }

    public function getPrecceedingRevenueShareConvertion(): array
    {
        if ($this->getMetaData()->has(self::METADATA_PRECCEEDING_CONVERTIONS)) {
            return $this->getMetaData()->get(self::METADATA_PRECCEEDING_CONVERTIONS);
        }

        return [];
    }

    private function setPrecedingRevenueShareFromRunningRevenueShare(MemberRunningRevenueShare $memberRunningRevenueShare): void
    {
        if ($memberRunningRevenueShare->isConditionMet()) {
            $this->setPrecedingRevenueShare(0);
            $this->setMetaData($this->getMetaData()->remove(self::METADATA_PRECCEEDING_CONVERTIONS));
        } else {
            $precedingRevenueShare = Number::add(
                $memberRunningRevenueShare->getPrecedingRevenueShare(),
                $memberRunningRevenueShare->getRevenueShare()
            );
            $this->setPrecedingRevenueShare($precedingRevenueShare);

            $this->setMetaData($this->getMetaData()->set(
                self::METADATA_PRECCEEDING_CONVERTIONS,
                $memberRunningRevenueShare->getTotalRevenueShareConvertion()
            ));
        }
    }

    public function getPayout(): string
    {
        if ($this->isConditionMet()) {
            return $this->getTotalRevenueShare();
        }

        return '0';
    }

    public function addRevenueShareConvertion(
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

    public function getRevenueSharePeriodId(): int
    {
        return $this->getRevenueSharePeriod()->getId();
    }
}
