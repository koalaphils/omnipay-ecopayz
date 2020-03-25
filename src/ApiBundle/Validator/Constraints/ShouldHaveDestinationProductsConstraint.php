<?php

namespace ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ShouldHaveDestinationProductsConstraint extends Constraint
{
    public $message = 'Transfer transactions should have destination products.';
}