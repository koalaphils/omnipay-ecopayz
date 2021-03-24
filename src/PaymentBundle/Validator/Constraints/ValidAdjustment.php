<?php

namespace PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidAdjustment extends Constraint
{
    public $emptyAmounts = 'Either amount or percentage should not be empty.';
    public $bothFieldsFilled = 'Either amount or percentage should have a value.';
}
