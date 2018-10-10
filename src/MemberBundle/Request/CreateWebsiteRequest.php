<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer as Member;

class CreateWebsiteRequest
{
    private $website;
    private $member;

    public static function fromEntity(Member $member): CreateWebsiteRequest
    {
        $request = new CreateWebsiteRequest();
        $request->member = $member;
        $request->website = '';

        return $request;
    }

    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    public function getWebsite(): string
    {
        if (is_null($this->website)) {
            return '';
        }

        return (string) $this->website;
    }

    public function getMember(): Member
    {
        return $this->member;
    }
}
