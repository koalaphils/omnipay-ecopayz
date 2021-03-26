<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidRangeValidator extends ConstraintValidator
{
    public function validate($entity, Constraint $constraint)
    {
        $revenueShareSettings = $revenueShareSettingsUnSort = $entity->getRevenueShareSettings();
        usort($revenueShareSettings, function ($field1, $field2) {
            return $field1['min'] - $field2['min'];
        });

        for ($i = 0; $i < count($revenueShareSettings); $i++) {
            if (!isset($revenueShareSettings[$i])) {
                continue;
            }
            $currentSetting = $revenueShareSettings[$i];
            if ($i > 0) {
                $prevSetting = $revenueShareSettings[$i - 1];
                if ($currentSetting['min'] <= $prevSetting['min']) {
                    $path = 'revenueShareSettings' . "[" . $i . "]" . "[min]";
                    $this->context->buildViolation($constraint->invalidRangeFrom)
                        ->setParameter('revenueShareSettings', "min ".$prevSetting['min'])
                        ->atPath($path)
                        ->addViolation();
                }

                if ($currentSetting['min'] <= $prevSetting['max']) {
                    $index = array_search($currentSetting['min'], array_column($revenueShareSettingsUnSort, 'min'));
                    if ($index) {
                        $path = 'revenueShareSettings' . "[" . $index . "]" . "[min]";
                        $this->context->buildViolation($constraint->invalidRangeFrom)
                            ->setParameter('revenueShareSettings', "max ".$prevSetting['max'])
                            ->atPath($path)
                            ->addViolation();
                    }
                }
            }
        }
    }
}