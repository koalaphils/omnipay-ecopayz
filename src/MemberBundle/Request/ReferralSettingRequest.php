<?php

declare(strict_types = 1);

namespace MemberBundle\Request;

class ReferralSettingRequest
{
    /**
     * @var int
     */
    private $cookieExpiration = 0;

    public function getCookieExpiration(): int
    {
        return $this->cookieExpiration;
    }

    public function getCookieExpirationAsSeconds(): int
    {
        return $this->getCookieExpiration() / 86400;
    }

    public function setCookieExpiration(int $cookieExpiration): self
    {
        $this->cookieExpiration = $cookieExpiration;

        return $this;
    }
}