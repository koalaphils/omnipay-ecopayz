<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Entity;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;

/**
 * This classs must be name as ProductCommissionPercentage
 * This will be use as the default commission percentage for commissions
 */
class ProductCommission extends Entity implements ActionInterface, VersionableInterface
{
    use Traits\ActionTrait;
    use Traits\VersionableTrait;

    private $product;
    private $commission;

    public function __construct()
    {
        $this->commission = 0;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getCommission(): string
    {
        return (string) $this->commission;
    }

    public function setCommission(string $commission): void
    {
        $this->commission = $commission;
    }

    public function generateResourceId(): string
    {
        return $this->getProduct()->getId();
    }

    public function getVersionedProperties(): array
    {
        return [
            'commission'
        ];
    }
}
