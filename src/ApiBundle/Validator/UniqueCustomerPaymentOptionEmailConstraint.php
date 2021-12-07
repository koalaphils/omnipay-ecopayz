<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class UniqueCustomerPaymentOptionEmailConstraint extends Constraint
{
	protected $emailViolationMessage = 'Email is not available';
	protected $accountIdViolationMessage = 'Account ID is not available';
	protected $accountIdUsdtViolationMessage = 'USDT Sender Address already in use.';

	public function getTargets()
	{
		return self::CLASS_CONSTRAINT;
	}

	public function getEmailViolationMessage(): string
	{
		return $this->emailViolationMessage;
	}

	public function getAccountIdViolationMessage(): string
	{
		return $this->accountIdViolationMessage;
	}

	public function getAccountIdUsdtViolationMessage(): string
	{
		return $this->accountIdUsdtViolationMessage;
	}
}