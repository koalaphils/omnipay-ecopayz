<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use TwoFactorBundle\Provider\TwoFactorRegistry;

class IsCodeCorrectValidator extends ConstraintValidator
{
    /**
     * @var TwoFactorRegistry
     */
    private $twoFactorRegistry;

    public function __construct(TwoFactorRegistry $twoFactorRegistry)
    {
        $this->twoFactorRegistry = $twoFactorRegistry;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        $message = $constraint->getMessage();
        $payloadPath = $constraint->getPayloadPath();
        $expLang = new ExpressionLanguage();

        if ($payloadPath !== '') {
            $payload = $expLang->evaluate($payloadPath, [
                'value' => $value,
                'object' => $this->context->getObject(),
            ]);
        }

        $valid = $this->twoFactorRegistry->validateCode($value, $payload);

        if (!$valid) {
            $this->context->buildViolation($message)->addViolation();
        }
    }
}