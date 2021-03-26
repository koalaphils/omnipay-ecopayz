<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class SameProductConstraint extends Constraint
{
    public $message = 'Cannot have the same product on one transaction';
}
