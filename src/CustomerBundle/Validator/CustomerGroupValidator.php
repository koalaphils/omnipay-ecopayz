<?php

namespace CustomerBundle\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DbBundle\Entity\CustomerGroupGateway;

class CustomerGroupValidator
{
    public static function validateUniqueGateway(CustomerGroupGateway $customerGroupGateway, ExecutionContextInterface $context, $payload)
    {
        $customerGroupGateways = $customerGroupGateway->getCustomerGroup()->getGateways();
        $hasDuplicate = false;
        foreach ($customerGroupGateways as $customerGroupGatewayToCompare) {
            if (spl_object_hash($customerGroupGatewayToCompare) === spl_object_hash($customerGroupGateway)) {
                break;
            }
            if ($customerGroupGateway->getGateway()->getId() === $customerGroupGatewayToCompare->getGateway()->getId()) {
                $hasDuplicate = true;
                break;
            }
        }

        if ($hasDuplicate) {
            $context
                ->buildViolation("The gateway was been already used")
                ->atPath("gateway")
                ->addViolation()
            ;
        }
    }
}
