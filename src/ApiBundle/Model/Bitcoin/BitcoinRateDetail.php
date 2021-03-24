<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Model\Bitcoin;

/**
 * Description of BitcoinRateDetail
 *
 * @author cydrick
 */
class BitcoinRateDetail
{
    public const ADJUSTMENT_TYPE_FIXED = 'fixed';
    public const ADJUSTMENT_TYPE_PERCENTAGE = 'percentage';
    
    private $rangeStart;
    private $rangeEnd;
    private $adjustment;
    private $adjustmentType;
    
    public function setRangeStart(?string $rangeStart): self
    {
        $this->rangeStart = $rangeStart ?? '';
        
        return $this;
    }
    
    public function getRangeStart(): string
    {
        return $this->rangeStart ?? '';
    }
    
    public function setRangeEnd(?string $rangeEnd): self
    {
        $this->rangeEnd = $rangeEnd ?? '';
        
        return $this;
    }
    
    public function getRangeEnd(): string
    {
        return $this->rangeEnd ?? '';
    }
    
    public function setAdjustment(?string $adjustment): self
    {
        $this->adjustment = $adjustment ?? '0';
        
        return $this;
    }
    
    public function getAdjustment(): string
    {
        return $this->adjustment ?? '0';
    }
    
    public function setAdjustmentType(?string $adjustmentType): self
    {
        $this->adjustmentType = $adjustmentType ?? self::ADJUSTMENT_TYPE_FIXED;
        
        return $this;
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
            'rangeStart' => $this->getRangeStart(),
            'rangeEnd' => $this->getRangeEnd(),
            'adjustment' => $this->getAdjustment(),
            'adjustmentType' => $this->getAdjustmentType(),
        ];
    }
}
