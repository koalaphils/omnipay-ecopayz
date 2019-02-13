<?php

namespace PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidRange extends Constraint
{
    public $invalidRangeFrom = 'This should be greater than the previous range which is {{ prevRangeFrom }}.';
    public $emptyAmounts = 'Either amount or percentage should not be empty.';
    public $bothFieldsFilled = 'Either amount or percentage should have a value.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
