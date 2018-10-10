<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;

/**
 * Description of UpdateContactRequest
 *
 * @author cydrick
 */
class UpdateContactRequest
{
    private $customer;
    private $contacts;

    public static function fromEntity(Customer $customer): UpdateContactRequest
    {
        $request = new UpdateContactRequest();
        $request->customer = $customer;
        $request->contacts = $customer->getContacts();

        return $request;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    public function setContacts(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}
