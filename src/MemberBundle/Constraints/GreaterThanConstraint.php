<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class GreaterThanConstraint extends Constraint
{
    public $message = '{{ second }} should be greater than {{ first }}.';
    public $first = '';
    public $second = '';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
