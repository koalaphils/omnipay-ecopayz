<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class EqualConstraint extends Constraint
{
    public $message = '{{ first }} should be equal to {{ second }}.';
    public $first = '';
    public $second = '';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
