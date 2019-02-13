<?php

namespace PaymentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidRangeValidator extends ConstraintValidator
{
    public function validate($rateSettingsDTO, Constraint $constraint)
    {
        $rateSettings = array_values($rateSettingsDTO->getBitcoinRateSettings());

        for ($i = 0; $i < count($rateSettings); $i++) {
            if (!isset($rateSettings[$i])) {
                continue;
            }
            $currentSetting = $rateSettings[$i];
            if ($i > 0) {
                $prevSetting = $rateSettings[$i - 1];

                if ($currentSetting->getRangeFrom() <= $prevSetting->getRangeFrom()) {
                    $this->context->buildViolation($constraint->invalidRangeFrom)
                        ->setParameter('{{ prevRangeFrom }}', $prevSetting->getRangeFrom())
                        ->atPath('bitcoinRateSettings[' . $i  . '].rangeFrom')
                        ->addViolation();
                }
            }
            
            if (empty($currentSetting->getFixedAdjustment()) && empty($currentSetting->getPercentageAdjustment())) {
                $this->context->buildViolation($constraint->emptyAmounts)
                    ->atPath('bitcoinRateSettings[' . $i  . '].fixedAdjustment')
                    ->addViolation();

                $this->context->buildViolation($constraint->emptyAmounts)
                    ->atPath('bitcoinRateSettings[' . $i  . '].percentageAdjustment')
                    ->addViolation();
            }

            if ((!empty($currentSetting->getFixedAdjustment()) || is_numeric($currentSetting->getFixedAdjustment())) && 
                    (!empty($currentSetting->getPercentageAdjustment())|| is_numeric($currentSetting->getPercentageAdjustment()))) {
                $this->context->buildViolation($constraint->bothFieldsFilled)
                    ->atPath('bitcoinRateSettings[' . $i  . '].fixedAdjustment')
                    ->addViolation()
                ;

                $this->context->buildViolation($constraint->bothFieldsFilled)
                    ->atPath('bitcoinRateSettings[' . $i  . '].percentageAdjustment')
                    ->addViolation()
                ;
            }
        }
    }
}
