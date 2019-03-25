<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Model;


class Player
{
    private const STATUS_ACTIVE = 'ACTIVE';
    private const STATUS_INACTIVE = 'INACTIVE';
    private const STATUS_SUSPENDED = 'SUSPENDED';
    private const STATUS_SUSPENDED_BY_COMPANY = 'SUSPENDED_BY_COMPANY';
    private const STATUS_CLOSED = 'CLOSED';

    /**
     * @var string
     */
    private $userCode;

    /**
     * @var string
     */
    private $loginId;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $availableBalance;

    /**
     * @var string
     */
    private $outstanding;

    /**
     * @var \DateTimeImmutable
     */
    private $createdDate;

    /**
     * @var string
     */
    private $createdBy;

    public static function create(array $data): self
    {
        $instance = new static();
        $instance->userCode = $data['userCode'];
        $instance->loginId = $data['loginId'];
        $instance->firstName = $data['firstName'];
        $instance->lastName = $data['lastName'];
        $instance->status = $data['status'];
        $instance->availableBalance = (string) $data['availableBalance'];
        $instance->outstanding = (string) $data['outstanding'];
        $instance->createdDate = (new \DateTimeImmutable($data['createdDate'] . ' -04:00'))->setTimezone(new \DateTimeZone('UTC'));
        $instance->createdBy = $data['createdBy'];

        return $instance;
    }

    public function userCode(): string
    {
        return $this->userCode;
    }

    public function loginId(): string
    {
        return $this->loginId;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function availableBalance(): string
    {
        return $this->availableBalance;
    }

    public function outstanding(): string
    {
        return $this->outstanding;
    }

    public function createdDate(): \DateTimeImmutable
    {
        return $this->createdDate;
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }
}