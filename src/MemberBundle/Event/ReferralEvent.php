<?php

namespace MemberBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use DbBundle\Entity\Customer as Member;

class ReferralEvent extends Event
{
    private $referrer;
    private $referral;

    public function __construct(Member $referrer, Member $referral)
    {
        $this->referrer = $referrer;
        $this->referral = $referral;
    }

    public function getReferrer(): Member
    {
        return $this->referrer;
    }

    public function getReferral(): Member
    {
        return $this->referral;
    }
}
