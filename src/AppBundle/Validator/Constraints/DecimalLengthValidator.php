<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\ValueObject\Number;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DecimalLengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $pieces = explode('.', $value);
        $decimalLength = strlen(end($pieces));
        $maxLength = $constraint->getLength();

        if ($this->validateLength($decimalLength, $maxLength)) {
            $this->context->buildViolation($constraint->getMessage())
                ->setParameter('{{ length }}', $maxLength)
                ->addViolation();
        }
    }

    private function validateLength(int $length, int $maxLength): bool
    {
        $length = new Number($length);

        return $length->greaterThan($maxLength);
    }
}