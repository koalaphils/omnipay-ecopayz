<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;

class UpdatePasswordRequest
{
    private $customer;
    private $password;
    private $confirmPassword;

    private function __construct()
    {
        $this->password = '';
        $this->confirmPassword = '';
    }

    public static function fromEntity(Customer $customer): UpdatePasswordRequest
    {
        $request = new UpdatePasswordRequest();
        $request->customer = $customer;

        return $request;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getConfirmPassword(): ?string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassword(?string $confirmPassword): void
    {
        $this->confirmPassword = $confirmPassword;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}
