<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer as Member;

class SendResetPasswordRequest
{
    private $member;
    private $origin;

    private function __construct()
    {
        $this->origin = '';
    }

    public static function fromEntity(Member $member): SendResetPasswordRequest
    {
        $request = new SendResetPasswordRequest();
        $request->member = $member;

        return $request;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): void
    {
        $this->origin = $origin;
    }

    public function getMember(): Member
    {
        return $this->member;
    }
}
