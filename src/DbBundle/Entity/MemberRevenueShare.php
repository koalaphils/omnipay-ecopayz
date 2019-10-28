<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\VersionableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use DbBundle\Utils\VersionableUtils;
use DbBundle\Entity\Customer as Member;

class MemberRevenueShare extends Entity implements ActionInterface, VersionableInterface
{
    use ActionTrait;
    use VersionableTrait;

    const REVENUE_SHARE_STATUS_ACTIVE = 1;
    const REVENUE_SHARE_STATUS_SUSPENDED = 0;
    const REVENUE_RANGE_MIN = 0;
    const REVENUE_RANGE_MAX = 0;
    const REVENUE_PERCENTAGE_MIN = 0;
    const REVENUE_PERCENTAGE_MAX = 0;
    const PINNACLE_PRODUCT_ID = 1;
    private $member;
    private $product;
    private $rangeMin;
    private $rangeMax;
    private $percentage;
    private $status;
    private $settings;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
        $this->rangeMin = self::REVENUE_RANGE_MIN;
        $this->rangeMax = self::REVENUE_RANGE_MAX;
        $this->percentage = self::REVENUE_PERCENTAGE_MIN;
        $this->status = self::REVENUE_SHARE_STATUS_ACTIVE;
        $this->version = 1;
        $this->versions = new ArrayCollection();
        $this->isLatest = true;
        $this->settings = [];
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setMember(Member $member): MemberRevenueShare
    {
        $this->member = $member;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): MemberRevenueShare
    {
        $this->product = $product;

        return $this;
    }

    public function setSettings(array $settings): MemberRevenueShare
    {
        $this->settings = $settings;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getPercentage(): string
    {
        return (string) $this->percentage;
    }

    public function setPercentage(string $percentage): MemberRevenueShare
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getRangeMin(): string
    {
        return (string) $this->rangeMin;
    }

    public function setRangeMin(string $rangeMin): MemberRevenueShare
    {
        $this->rangeMin = $rangeMin;

        return $this;
    }

    public function getRangeMax(): string
    {
        return (string) $this->rangeMax;
    }

    public function setRangeMax(string $rangeMax): MemberRevenueShare
    {
        $this->rangeMax = $rangeMax;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): MemberRevenueShare
    {
        $this->status = $status;

        return $this;
    }

    public function generateResourceId(): string
    {
        return 'revenueShare-' . $this->getMember()->getId() . '-' . $this->getProduct()->getId();
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
            'settings',
            'status',
        ];
    }
}