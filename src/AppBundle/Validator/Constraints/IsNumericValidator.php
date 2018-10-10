<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsNumericValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!is_numeric($value)) {
            $this->context->buildViolation($constraint->getMessage())->setParameter('{{ value }}', $value)->addViolation();
        }
    }
}
