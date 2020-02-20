<?php

namespace MemberBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer as Member;

class KycFileEvent extends Event
{
    private $member;
    private $files;
    private $details;

    public function __construct(Member $member, array $files, array $details)
    {
        $this->member = $member;
        $this->files = $files;
        $this->details = $details;
    }

    public function getCustomer(): Member
    {
        return $this->member;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
