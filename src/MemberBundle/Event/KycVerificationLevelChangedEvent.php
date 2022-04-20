<?php

namespace MemberBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer as Member;

class KycVerificationLevelChangedEvent extends Event
{
    private $member;
    private $verificationLevel;

    public function __construct(Member $member, string $verificationLevel)
    {
        $this->member = $member;
        $this->verificationLevel = $verificationLevel;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getVerificationLevel(): string
    {
        return $this->verificationLevel;
    }
}