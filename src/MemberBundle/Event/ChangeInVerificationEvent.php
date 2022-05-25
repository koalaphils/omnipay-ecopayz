<?php

namespace MemberBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer as Member;

class ChangeInVerificationEvent extends Event
{
    private $member;

    public function __construct(Member $member)
    {
        $this->member = $member;
    }

    public function getMember(): Member
    {
        return $this->member;
    }
}
