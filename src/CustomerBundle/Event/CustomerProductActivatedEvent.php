<?php

namespace CustomerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use DbBundle\Entity\CustomerProduct;

class CustomerProductActivatedEvent extends Event
{
    private $customerProduct;

    public function __construct(CustomerProduct $customerProduct)
    {
        $this->customerProduct = $customerProduct;
    }

    public function getCustomerProduct(): CustomerProduct
    {
        return $this->customerProduct;
    }
}
