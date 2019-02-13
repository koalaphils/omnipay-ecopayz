<?php

namespace DbBundle\Entity;

class InactiveMember extends Entity
{
    /**
     * @var \DbBundle\Entity\Member
     */
    protected $member;

    protected $dateAdded;

    public function __construct()
    {
        $this->setDateAdded(new \DateTimeImmutable());
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Member $member
     * @return InactiveMembers
     */
    public function setMember($member)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     * @return InactiveMembers
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getMemberId(): int
    {
        return $this->getMember()->getId();
    }

    public function getMemberFullname(): string
    {
        return $this->getMember()->getFullName() ?? '';
    }

    public function getMemberCurrencyCode(): string
    {
        return $this->getMember()->getCurrencyCode() ?? '';
    }

    public function getMemberJoinedDate(): \DateTimeInterface
    {
        return $this->getMember()->getJoinedAt();
    }

    public function getTotalProductBalance(): string
    {
        return (string) ($this->getMember()->getAvailableBalance() ?? '0.00');
    }

}
