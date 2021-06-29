<?php

namespace DbBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class AuditRevision extends Entity
{
    const TYPE_MEMBER = 'member';
    const TYPE_SUPPORT = 'support';

    private $timestamp;

    private $clientIp;

    private $user;

    private $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function setTimestamp($timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setClientIp($clientIp): self
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser():? User
    {
        return $this->user;
    }

    public function addLog(AuditRevisionLog $log)
    {
        $log->setAuditRevision($this);
        $this->logs[] = $log;

        return $this;
    }

    public function removeLog(AuditRevisionLog $log)
    {
        $this->logs->removeElement($log);
    }

    public function getLogs()
    {
        return $this->logs;
    }
}