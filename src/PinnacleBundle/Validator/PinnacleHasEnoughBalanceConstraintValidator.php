<?php

declare(strict_types = 1);

namespace PinnacleBundle\Validator;

use AppBundle\ValueObject\Number;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PinnacleHasEnoughBalanceConstraintValidator extends ConstraintValidator
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    public function __construct(PinnacleService $pinnacleService)
    {
        $this->pinnacleService = $pinnacleService;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof PinnacleHasEnoughBalanceConstraint)) {
            return;
        }

        if ($constraint->getTransactedExpression() !== null) {
            $expLang = new ExpressionLanguage();
            $transacted = $expLang->evaluate($constraint->getTransactedExpression(), ['object' => $this->context->getObject()]);
            if ($transacted) {
                return;
            }
        }

        $userCode = $constraint->getUserCode();
        if ($constraint->getIsUserCodeExpression()) {
            $expLang = new ExpressionLanguage();
            $userCode = $expLang->evaluate($constraint->getUserCode(), ['object' => $this->context->getObject()]);
        }


        $availableBalance = new Number($this->pinnacleService->getPlayerComponent()->getPlayer($userCode)->availableBalance());
        if ($availableBalance->lessThan($value)) {
            $this->context->buildViolation($constraint->getMessage())->addViolation();
        }
    }
}