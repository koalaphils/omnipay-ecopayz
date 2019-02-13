<?php

namespace PaymentBundle\Model;

class MemberToken implements \Payum\Core\Security\TokenInterface
{
    private $afterUrl;
    private $gatewayName;
    private $hash;
    private $targetUrl;
    private $details;

    public function getAfterUrl(): string
    {
        return $this->afterUrl;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function setAfterUrl($afterUrl)
    {
        $this->afterUrl = $afterUrl;
    }

    public function setDetails($details): void
    {
        $this->details = $details;
    }

    public function setGatewayName($gatewayName)
    {
        $this->gatewayName = $gatewayName;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }
}
