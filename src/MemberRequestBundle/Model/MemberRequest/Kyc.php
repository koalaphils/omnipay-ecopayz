<?php

namespace MemberRequestBundle\Model\MemberRequest;

use \DateTime;

class Kyc
{
    private $filename;
    private $remark;
    private $status;
    private $requestedAt;
    private $isDeleted;

    public function __construct()
    {
        $this->filename = '';
        $this->remark = '';
        $this->status = null;
        $this->isDeleted = false;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function setRemark(string $remark): self
    {
            $this->remark = $remark;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRequestedAt(): ?DateTime
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(): self
    {
        $this->requestedAt = new DateTime('now');

        return $this;
    }

    public function wasStatusValidated(): bool
    {
        return !is_null($this->getStatus());
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}