<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Customer as Member;

class MemberReferralName extends Entity implements ActionInterface, TimestampInterface, AuditAssociationInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $name;
    private $isActive;
    private $member;

    public function __construct()
    {
        $this->isActive = true;
    }

    public static function create(Member $member): MemberReferralName
    {
        $memberReferralName = new self();

        $memberReferralName->setMember($member);

        return $memberReferralName;
    }

    public function setName(string $name): MemberReferralName
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setIsActive(bool $isActive): MemberReferralName
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setMember(Member $member): MemberReferralName
    {
        $this->member = $member;

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function activate(): void
    {
        $this->setIsActive(true);
    }

    public function suspend(): void
    {
        $this->setIsActive(false);
    }

    public function getMemberId(): int
    {
        return $this->getMember()->getId();
    }

    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    public function getCategory(): int
    {
        return AuditRevisionLog::CATEGORY_MEMBER_REFERRAL_NAME;
    }

    public function getIgnoreFields(): array
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields(): array
    {
        return ['member'];
    }

    public function getIdentifier(): ?int
    {
        return $this->getId();
    }

    public function getLabel(): string
    {
        return sprintf('%s (%s)', $this->getMemberFullName(), $this->getName());
    }

    public function getAuditDetails(): array
    {
        return ['name' => $this->getName()];
    }

    public function isAudit(): bool
    {
        return true;
    }

    public function getAssociationFieldName(): string
    {
        return $this->getName();
    }

    private function getMemberFullName(): string
    {
        return $this->getMember()->getFullName();
    }
}

