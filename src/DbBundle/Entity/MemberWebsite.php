<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Customer as Member;

class MemberWebsite extends Entity implements ActionInterface, TimestampInterface, AuditAssociationInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $website;
    private $isActive;
    private $member;

    public function __construct()
    {
        $this->isActive = true;
    }

    public static function create(Member $member): MemberWebsite
    {
        $memberWebsite = new self();

        $memberWebsite->setMember($member);

        return $memberWebsite;
    }

    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    public function getMember(): Member
    {
        return $this->member;
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
        return AuditRevisionLog::CATEGORY_MEMBER_WEBSITE;
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
        return sprintf('%s (%s)', $this->getMemberFullName(), $this->getWebsite());
    }

    public function getAuditDetails(): array
    {
        return ['website' => $this->getWebsite()];
    }

    public function isAudit(): bool
    {
        return true;
    }

    public function getAssociationFieldName(): string
    {
        return $this->getWebsite();
    }

    private function getMemberFullName(): string
    {
        return $this->getMember()->getFullName();
    }

    public function suspend(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }
}

