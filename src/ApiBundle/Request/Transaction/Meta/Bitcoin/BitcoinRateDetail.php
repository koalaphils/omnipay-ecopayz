<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta\Bitcoin;

class BitcoinRateDetail
{
    public const ADJUSTMENT_TYPE_FIXED = 'fixed';
    public const ADJUSTMENT_TYPE_PERCENTAGE = 'percentage';

    /**
     * @var string
     */
    private $rangeStart;

    /**
     * @var string
     */
    private $rangeEnd;

    /**
     * @var string
     */
    private $adjustment;

    /**
     * @var string
     */
    private $adjustmentType;

    public static function createFromArray(array $data): self
    {
        $instance = new static();
        $instance->rangeStart = (string) ($data['range_start'] ?? '');
        $instance->rangeEnd = (string) ($data['range_end'] ?? '');
        $instance->adjustment = (string) ($data['adjustment'] ?? '');
        $instance->adjustmentType = (string) ($data['adjustment_type'] ?? '');

        return $instance;
    }

    public function getRangeStart(): string
    {
        return $this->rangeStart ?? '';
    }

    public function getRangeEnd(): string
    {
        return $this->rangeEnd ?? '';
    }

    public function getAdjustment(): string
    {
        return $this->adjustment ?? '0';
    }

    public function getAdjustmentType(): string
    {
        return $this->adjustmentType ?? self::ADJUSTMENT_TYPE_FIXED;
    }

    public function isFixed(): bool
    {
        return $this->getAdjustmentType() === self::ADJUSTMENT_TYPE_FIXED;
    }

    public function isPercentage(): bool
    {
        return $this->getAdjustmentType() === self::ADJUSTMENT_TYPE_PERCENTAGE;
    }

    public function toArray(): array
    {
        return [
            'range_start' => $this->rangeStart,
            'range_end' => $this->rangeEnd,
            'adjustment' => $this->adjustment,
            'adjustment_type' => $this->adjustmentType,
        ];
    }
}