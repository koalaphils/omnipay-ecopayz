<?php

namespace MemberRequestBundle\Model\MemberRequest;

use \DateTime;
use DbBundle\Entity\Customer as Member;

class ProductPassword
{
    private $memberProductId;
    private $createdAt;
    private $password;
    private $isActive;

    public function __construct()
    {
        $this->memberProductId = 0;
        $this->setCreatedAt();
        $this->setPassword();
        $this->isActive = 0;
    }

    public function getMemberProductId(): int
    {
        return $this->memberProductId;
    }

    public function setMemberProductId(int $memberProductId): self
    {
        $this->memberProductId = $memberProductId;

        return $this;
    }

    public function isActive(): int
    {
        return $this->isActive;
    }

    public function setIsActive(int $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTime('now');

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword($password = null): self
    {
        $this->password = is_null($password) ? generate_code(12, false, 'lud') : $password;

        return $this;
    }

    public function setMember(Member $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }
}