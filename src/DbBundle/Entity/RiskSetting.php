<?php

namespace DbBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\ProductRiskSetting;
use DbBundle\Entity\Product;
use DbBundle\Utils\VersionableUtils;

class RiskSetting extends Entity implements VersionableInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\VersionableTrait;

    private $riskId;

    private $isActive;

    private $productRiskSettings;

    public function __construct()
    {
        $this->productRiskSettings = new ArrayCollection();
    }

    public function setRiskId(String $riskId): self
    {
        $this->riskId = $riskId;

        return $this;
    }

    public function getRiskId(): String
    {
        if (is_null($this->riskId)) {
            $this->setRiskId('-');
        }
        return $this->riskId;
    }

    public function getIsActive(): bool
    {
        if (is_null($this->isActive)) {
            $this->setIsActive(false);
        }
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive === true;
    }

    public function getProductRiskSettings()
    {
        return $this->productRiskSettings;
    }

    public function addProductRiskSettings($productRiskSettings): self
    {
        $this->productsRiskSettings[] = $productRiskSettings;

        return $this;
    }

    public function setProductRiskSettings($productRiskSettings): self
    {
        $this->productRiskSettings = $productRiskSettings;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->isActive === false;
    }

    public function suspend(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function addAllAvailableProducts($products): void
    {
        foreach ($products as $product) {
            if (!$this->isAlreadyOnSetting($product)) {
                $productSetting = ProductRiskSetting::createProductRiskSetting($product);
                $productSetting->setRiskSetting($this);
                $this->productRiskSettings[] = $productSetting;
            }
        }
    }

    protected function isAlreadyOnSetting(Product $product): bool
    {
        foreach ($this->productRiskSettings as $productRiskSettings) {
            if ($productRiskSettings->getProduct() === $product) {
                return true;
            }
        }

        return false;
    }

    // Override default preserveOriginal()
    public function preserveOriginal(): void
    {
        VersionableUtils::preserveOriginal($this);
    }

    public function generateResourceId(): string
    {
        return uniqid();
    }

    public function getVersionedProperties(): array
    {
        return [
            'isActive', 
            'riskId', 
            'productRiskSettings'
        ];
    }

    public function getCategory(): string
    {
        return AuditRevisionLog::CATEGORY_RISK_SETTING;
    }

    public function getIgnoreFields(): array
    {
        return [];
    }

    public function getAssociationFields(): array
    {
        return ['productRiskSettings'];
    }

    public function getIdentifier(): ?int
    {
        return $this->getId();
    }

    public function getLabel(): string
    {
        return $this->getRiskId();
    }

    public function isAudit(): bool
    {
        return false;
    }

    public function getAssociationFieldName(): string
    {
        return $this->getRiskId();
    }

    public function getAuditDetails(): array
    {
        return ['riskId' => $this->getRiskId(), 'isActive' => $this->getIsActive()];
    }
}
