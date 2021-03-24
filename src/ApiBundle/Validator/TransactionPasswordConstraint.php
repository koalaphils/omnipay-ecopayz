<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class TransactionPasswordConstraint extends Constraint
{
    public $message = 'Incorrect transaction password';
}
