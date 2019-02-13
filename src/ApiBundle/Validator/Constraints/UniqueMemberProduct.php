<?php

namespace ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueMemberProduct extends Constraint
{
    public $message = '{{ products }} product/s already exists in your account.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}