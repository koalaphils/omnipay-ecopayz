<?php

namespace MemberBundle\Request;

class UpdateMemberProductRequest
{
    private $username;
    private $product;
    private $balance;
    private $active;
    private $brokerage;
    private $brokerageFirstName;
    private $brokerageLastName;
    private $customerProduct;

    public function __construct() {
        $this->username = '';
        $this->balance = 0;
        $this->active = true;
        $this->brokerageFirstName = '';
        $this->brokerageLastName = '';
    }

    public function getCustomerProduct(): ?int
    {
        return (int) $this->customerProduct;
    }

    public function setCustomerProduct(int $customerProduct): void
    {
        $this->customerProduct = $customerProduct;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getProduct(): ?int
    {
        return $this->product;
    }

    public function setProduct(int $product): void
    {
        $this->product = $product;
    }

    public function getBalance(): string
    {
        return (string) $this->balance;
    }

    public function setBalance(string $balance): void
    {
        $this->balance = $balance;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getBrokerage(): ?string
    {
        return $this->brokerage;
    }

    public function setBrokerage(?string $brokerage): void
    {
        $this->brokerage = $brokerage;
    }

    public function getBrokerageFirstName(): ?string
    {
        return $this->brokerageFirstName;
    }

    public function setBrokerageFirstName(?string $firstName): void
    {
        $this->brokerageFirstName = $firstName;
    }

    public function getBrokerageLastName(): ?string
    {
        return $this->brokerageLastName;
    }

    public function setBrokerageLastName(?string $lastName): void
    {
        $this->brokerageLastName = $lastName;
    }
}
