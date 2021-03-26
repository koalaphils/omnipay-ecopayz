<?php

namespace MemberBundle\Event;

use DbBundle\Entity\Customer as Member;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;

class MemberProductRequestEvent extends Event
{
    private $member;
    private $memberProducts;

    public function __construct(Member $member, ArrayCollection $memberProducts)
    {
        $this->member = $member;
        $this->memberProducts = $memberProducts;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getMemberProducts(): ArrayCollection
    {
        return $this->memberProducts;
    }
}