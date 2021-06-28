<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\VersionableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use DbBundle\Utils\VersionableUtils;

/**
 * This should be name as MemberCommissionPercentage
 * This percentage will be use for calculating the commission.
 */
class MemberCommission extends Entity implements ActionInterface, VersionableInterface
{
    use ActionTrait;
    use VersionableTrait;

    const COMMISSION_STATUS_ACTIVE = 1;
    const COMMISSION_STATUS_SUSPENDED = 0;

    private $member;
    private $product;
    private $commission;
    private $status;


    public function __construct()
    {
        $this->customers = new ArrayCollection();
        $this->commission = 0;
        $this->status = self::COMMISSION_STATUS_ACTIVE;
        $this->version = 1;
        $this->versions = new ArrayCollection();
        $this->isLatest = true;
    }

    public function getMember(): Customer
    {
        return $this->member;
    }

    public function setMember(Customer $member): MemberCommission
    {
        $this->member = $member;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): MemberCommission
    {
        $this->product = $product;

        return $this;
    }

    public function getCommission(): string
    {
        return (string) $this->commission;
    }

    public function setCommission(string $commission): MemberCommission
    {
        $this->commission = $commission;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): MemberCommission
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::COMMISSION_STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::COMMISSION_STATUS_SUSPENDED;
    }

    public function activate(): void
    {
        $this->status = self::COMMISSION_STATUS_ACTIVE;
    }

    public function suspend(): void
    {
        $this->status = self::COMMISSION_STATUS_SUSPENDED;
    }

    public function generateResourceId(): string
    {
        return 'commission-' . $this->getMember()->getId() . '-' . $this->getProduct()->getId();
    }

    public function createResourceIdForSelf() : void
    {
        $this->setResourceId($this->generateResourceId());
    }

    public function preserveOriginal(): void
    {
        VersionableUtils::preserveOriginal($this);
    }

    public function getVersionedProperties(): array
    {
        return [
            'member',
            'product',
            'commission',
            'status',
        ];
    }
}
