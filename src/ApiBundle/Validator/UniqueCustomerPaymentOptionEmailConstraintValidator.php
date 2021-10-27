<?php

namespace ApiBundle\Validator;

use ApiBundle\Model\Transaction;
use ApiBundle\Request\Transaction\DepositRequest;
use ApiBundle\Request\Transaction\WithdrawRequest;
use AppBundle\Service\CustomerPaymentOptionService;
use AppBundle\Service\PaymentOptionService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueCustomerPaymentOptionEmailConstraintValidator extends ConstraintValidator
{
	private $cpoService;

	public function __construct(CustomerPaymentOptionService $cpoService)
	{
		$this->cpoService = $cpoService;
	}

	/**
	 * @param DepositRequest|WithdrawRequest $transactionModel
	 * @param Constraint $constraint
	 */
	public function validate($transactionModel, Constraint $constraint)
	{
		if (!$constraint instanceof UniqueCustomerPaymentOptionEmailConstraint) {
			throw new UnexpectedTypeException($constraint, UniqueCustomerPaymentOptionEmailConstraint::class);
		}

		$value = '';
		$field = '';

		if (PaymentOptionService::isConfiguredToUseEmail($transactionModel->getPaymentOptionType())) {
			$value = $transactionModel->getEmail() ?? '';
			if ($value === '') return;
			$field = 'email';
		}

		if (PaymentOptionService::isConfiguredToUseAccountId($transactionModel->getPaymentOptionType())) {
			$value = $transactionModel->getAccountId() ?? '';
			if ($value === '') return;
			$field = PaymentOptionService::USDT ? 'email' : 'account_id';
		}

		$availability = $this->cpoService->checkAvailability(
			$transactionModel->getMemberId(),
			$transactionModel->getPaymentOptionType(),
			$field,
			$value
		);

		if (!$availability['available']) {
			if ($field == 'account_id') {
				$this->context->buildViolation($constraint->getAccountIdViolationMessage())->addViolation();
			} else {
				if ($transactionModel->getPaymentOptionType() == PaymentOptionService::USDT) {
					$this->context->buildViolation($constraint->getAccountIdUsdtViolationMessage())->atPath($field)->addViolation();
				} else {
					$this->context->buildViolation($constraint->getEmailViolationMessage())->atPath($field)->addViolation();
				}
			}
		}
	}
}
