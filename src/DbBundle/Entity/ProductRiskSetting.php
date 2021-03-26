<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\Interfaces\VersionableChildInterface;
use DbBundle\Entity\Product;

class ProductRiskSetting extends Entity
{
    use Traits\ActionTrait;

    private $product;
    private $riskSetting;
    private $riskSettingPercentage;

    public function __construct()
    {
        $this->isLatest = true;
    }

    public static function createProductRiskSetting(Product $product): self
    {
        $productSetting = new self();

        $productSetting->setRiskSettingPercentage(0);
        $productSetting->setProduct($product);

        return $productSetting;
    }

    public function setRiskSetting($riskSetting): self
    {
        $this->riskSetting = $riskSetting;
 
        return $this;
    }

    public function getRiskSetting()
    {
        return $this->riskSetting;
    }

    public function getRiskSettingPercentage(): float
    {
        if ($this->riskSettingPercentage === null) {
            $this->riskSettingPercentage = 0.0;
        }
        return $this->riskSettingPercentage;
    }

    public function setRiskSettingPercentage($percentage)
    {
        $this->riskSettingPercentage = $percentage;
    }

    public function getProductName(): String
    {
        if (empty($this->product)) {
            return '';
        }

        return $this->product->getName();
    }

    public function isActive(): bool
    {
        return $this->product->isActive() == true;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }
}
