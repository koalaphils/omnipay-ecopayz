<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message;

class CodeModel
{
    protected const STATUS_USED = 1;
    protected const STATUS_UNUSED = 2;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var \DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @var \DateTimeImmutable
     */
    protected $expireAt;

    /**
     * @var int
     */
    protected $status = CodeModel::STATUS_UNUSED;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @param string|null $key
     * @return mixed|null
     */
    public function getPayload(?string $key = null)
    {
        if ($key === null) {
            return $this->payload;
        }

        return array_get($this->payload, $key);
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getExpireAt(): \DateTimeImmutable
    {
        return $this->expireAt;
    }

    public function setExpiredAt(\DateTimeImmutable $expiredAt): self
    {
        $this->expireAt = $expiredAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setToUsed(): self
    {
        $this->status = static::STATUS_USED;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->status === static::STATUS_USED;
    }

    public function isExpired(): bool
    {
        return $this->expireAt->getTimestamp() < (new \DateTimeImmutable('now'))->getTimestamp();
    }
}