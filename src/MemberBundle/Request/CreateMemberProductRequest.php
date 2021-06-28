<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct;

class CreateMemberProductRequest
{
    private $username;
    private $product;
    private $balance;
    private $active;
    private $customerProduct;

    public static function fromEntity(Customer $customer): CreateMemberProductRequest
    {
        $request = new CreateMemberProductRequest();
        $request->customerProduct = new CustomerProduct();
        $request->customerProduct->setCustomer($customer);
        $request->customerProduct->setCustomerID($customer->getId());
        
        return $request;
    }

    public function __construct() {
        $this->username = '';
        $this->balance = 0;
        $this->active = true;
    }

    public function getCustomerProduct(): CustomerProduct
    {
        return $this->customerProduct;
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
}