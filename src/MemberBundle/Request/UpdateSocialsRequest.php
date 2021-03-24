<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;

/**
 * Description of UpdateContactRequest
 *
 * @author cydrick
 */
class UpdateSocialsRequest
{
    private $customer;
    private $socials;

    public static function fromEntity(Customer $customer): UpdateSocialsRequest
    {
        $request = new UpdateSocialsRequest();
        $request->customer = $customer;
        $request->socials = $customer->getSocials();

        return $request;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getSocials(): array
    {
        return $this->socials;
    }

    public function setSocials(array $socials): void
    {
        $this->socials = $socials;
    }
}
