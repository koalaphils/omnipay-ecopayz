<?php

namespace ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\Customer;

class CustomerCreatedEvent extends Event
{
    private $customer;
    private $originUrl;

    public function __construct(Customer $customer, $originUrl)
    {
        $this->customer = $customer;
        $this->originUrl = $originUrl;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getOriginUrl(): string
    {
        return $this->originUrl;
    }
}
