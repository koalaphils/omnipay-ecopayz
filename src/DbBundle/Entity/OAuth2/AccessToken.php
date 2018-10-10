<?php

namespace DbBundle\Entity\OAuth2;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;

class AccessToken extends BaseAccessToken
{
    private $ipAddress;

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress($ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
}
