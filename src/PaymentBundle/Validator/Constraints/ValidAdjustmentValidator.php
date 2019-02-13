<?php

namespace PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidAdjustmentValidator extends ConstraintValidator
{
    public function validate($rateSetting, Constraint $constraint)
    {
        if (empty($rateSetting->getFixedAdjustment()) && empty($rateSetting->getPercentageAdjustment())) {
            $this->context->buildViolation($constraint->emptyAmounts)
                ->atPath('fixedAdjustment')
                ->addViolation();

            $this->context->buildViolation($constraint->emptyAmounts)
                ->atPath('percentageAdjustment')
                ->addViolation();
        }

        if ((!empty($rateSetting->getFixedAdjustment()) || is_numeric($rateSetting->getFixedAdjustment())) && 
            (!empty($rateSetting->getPercentageAdjustment())|| is_numeric($rateSetting->getPercentageAdjustment()))) {
                $this->context->buildViolation($constraint->bothFieldsFilled)
                    ->atPath('fixedAdjustment')
                    ->addViolation()
                ;

            $this->context->buildViolation($constraint->bothFieldsFilled)
                ->atPath('percentageAdjustment')
                ->addViolation()
            ;
        }
    }
}
