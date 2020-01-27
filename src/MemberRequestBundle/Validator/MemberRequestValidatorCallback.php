<?php

namespace MemberRequestBundle\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MemberRequestValidatorCallback
{
    public static function validatePassword($object, ExecutionContextInterface $context)
    {
        $subRequests = $object->getProductPasswordSubRequests();
        foreach ($subRequests as $key => $subRequest) {
            if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]?)[a-zA-Z0-9]{8,15}$/', $subRequest['password'], $matches)) {
                $context->buildViolation('Password is invalid.')
                    ->atPath('subRequests['. $key .'][password]')
                    ->addViolation();
            }
        }
    }
}
