<?php

namespace DbBundle\Entity;

/**
 * SmsCode
 */
class SmsCode extends Entity
{
    public const STATUS_QUE = '0';
    public const STATUS_SUCCESS = '1';
    public const STATUS_FAILURE = '2';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var int
     */
    private $value;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $memberPhoneNumber;

    /**
     * @var string
     */
    private $memberEmail;

    /**
     * @var string
     */
    private $status;

    /**
     * @var int
     */
    private $createdAt;

    /**
     * @var string
     */
    private $providerId;

    /**
     * @var string
     */
    private $sourcePhoneNumber;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getMemberPhoneNumber(): string
    {
        return $this->memberPhoneNumber;
    }

    public function setMemberPhoneNumber(string $memberPhoneNumber): self
    {
        $this->memberPhoneNumber = $memberPhoneNumber;

        return $this;
    }

    public function getMemberEmail(): string
    {
        return $this->memberEmail;
    }

    public function setMemberEmail(string $memberEmail): self
    {
        $this->memberEmail = $memberEmail;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        return $this->status;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    public function setProviderId(string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getSourcePhoneNumber(): string
    {
        return $this->sourcePhoneNumber;
    }

    public function setSourcePhoneNumber(string $sourcePhoneNumber): self
    {
        $this->sourcePhoneNumber = $sourcePhoneNumber;

        return $this;
    }
}
