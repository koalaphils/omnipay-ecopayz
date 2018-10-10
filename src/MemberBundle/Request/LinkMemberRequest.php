<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;

class LinkMemberRequest
{
    /**
     *
     * @var \Doctrine\ORM\PersistentCollection
     */
    private $referrals;
    private $member;

    public static function fromEntity(Customer $customer): LinkMemberRequest
    {
        $request = new LinkMemberRequest();
        $request->referrals = [];
        $request->member = $customer;

        return $request;
    }

    public function getReferrals(): array
    {
        return $this->referrals;
    }

    public function setReferrals(array $referrals): void
    {
        $this->referrals = $referrals;
    }

    public function getMember(): Customer
    {
        return $this->member;
    }
}
