<?php

namespace CustomerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use DbBundle\Entity\CustomerProduct;

class CustomerProductActivatedEvent extends Event
{
    private $customerProduct;
    private $details;

    public function __construct(CustomerProduct $customerProduct)
    {
        $this->customerProduct = $customerProduct;
        $this->details = [];
    }

    public function getCustomerProduct(): CustomerProduct
    {
        return $this->customerProduct;
    }

    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
