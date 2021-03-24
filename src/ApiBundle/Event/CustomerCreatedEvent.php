<?php

namespace ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer;

class CustomerCreatedEvent extends Event
{
    private $customer;
    private $originUrl;
    private $tempPassword;

    public function __construct(Customer $customer, string $originUrl, string $tempPassword)
    {
        $this->customer = $customer;
        $this->originUrl = $originUrl;
        $this->tempPassword = $tempPassword;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getOriginUrl(): string
    {
        return $this->originUrl;
    }

    public function getTempPassword(): string
    {
        return $this->tempPassword;
    }
}
