<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer as Member;

class CreateMemberReferralNameRequest
{
    private $name;
    private $isActive;
    private $member;

    public function __construct() {
        $this->name = '';
        $this->isActive = true;
    }

    public static function fromEntity(Member $member): CreateMemberReferralNameRequest
    {
        $request = new CreateMemberReferralNameRequest();
        $request->member = $member;

        return $request;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setMember(Member $member): void
    {
        $this->member = $member;
    }
}
