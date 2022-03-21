<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class VerifiedMemberAccountConstraint extends Constraint
{
    public $message = 'Member Account is not yet verified.';
}
