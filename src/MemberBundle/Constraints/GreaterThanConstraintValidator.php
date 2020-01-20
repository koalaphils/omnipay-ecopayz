<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;

class GreaterThanConstraintValidator extends ConstraintValidator
{
    public function validate($entity, Constraint $constraint)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $firstValue = $propertyAccessor->getValue($entity, $constraint->first);
        foreach ($firstValue as $index => $settingsPerRow) {
            $path = $constraint->first . "[" . $index . "]" . "[max]";
            if ($settingsPerRow['min'] >= $settingsPerRow['max']) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($path)
                    ->setParameter('{{ first }}', 'Min')
                    ->setParameter('{{ second }}', 'Max')
                    ->addViolation()
                ;
            }
        }
    }
}
