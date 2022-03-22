<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class VerifiedMemberAccountConstraint extends Constraint
{
    protected $accountValidationMessage = 'Member Account is not yet verified.';

    
	public function getTargets()
	{
		return self::CLASS_CONSTRAINT;
	}

    public function getAccountValidationMessage(): string
	{
		return $this->accountValidationMessage;
	}
}
