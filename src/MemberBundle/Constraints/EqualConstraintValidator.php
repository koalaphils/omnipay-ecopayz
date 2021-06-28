<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EqualConstraintValidator extends ConstraintValidator
{
    public function validate($entity, Constraint $constraint)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $firstValue = $propertyAccessor->getValue($entity, $constraint->first);
        $secondValue = $propertyAccessor->getValue($entity, $constraint->second);

        if ($firstValue !== $secondValue) {
            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->first)
                ->setParameter('{{ first }}', ucfirst(self::fromCamelCase($constraint->first)))
                ->setParameter('{{ second }}', self::fromCamelCase($constraint->second))
                ->addViolation()
            ;
        }
    }

    public static function fromCamelCase($camelCaseString): string 
    {
        $regex = '/(?<=[a-z])(?=[A-Z])/x';
        $words = preg_split($regex, $camelCaseString);

        return strtolower(join($words, ' '));
    }
}
