<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Utils\VersionableUtils;

class MarketingTool extends Entity implements ActionInterface, VersionableInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\VersionableTrait;

    private $affiliateLink;
    private $promoCode;
    private $member;

    public function __construct()
    {
        $this->isLatest = true;
    }

    public function getAffiliateLink(): ?string
    {
        return $this->affiliateLink;
    }

    public function setAffiliateLink(?string $affiliateLink): self
    {
        $this->affiliateLink = !is_null($affiliateLink) ? $affiliateLink : '';
        
        return $this;
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function setPromoCode(?string $promoCode): self
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    public function getMember(): Customer
    {
        return $this->member;
    }

    public function setMember(Customer $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function generateResourceId(): string
    {
        return 'affiliateLink-' . $this->getMember()->getId();
    }

    public function preserveOriginal(): void
    {
        VersionableUtils::preserveOriginal($this);
    }

    public function getVersionedProperties(): array
    {
        return [
            'member',
            'affiliateLink',
            'promoCode',
        ];
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return ['member'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getMember()->getFullName();
    }

    public function getAuditDetails(): array
    {
        return [];
    }

    public function isAudit()
    {
        return false;
    }
}