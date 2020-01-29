<?php

namespace MemberBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer as Member;

class KycFileEvent extends Event
{
    private $member;
    private $files;

    public function __construct(Member $member, array $files)
    {
        $this->member = $member;
        $this->files = $files;
    }

    public function getCustomer(): Member
    {
        return $this->member;
    }

    public function getFiles(): array
    {
        return $this->files;
    }
}
