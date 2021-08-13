<?php

namespace ApiBundle\Validator;

use ApiBundle\Model\Transaction;
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
	 * @param Transaction $transactionModel
	 * @param Constraint $constraint
	 */
	public function validate($transactionModel, Constraint $constraint)
	{
		if (!$constraint instanceof UniqueCustomerPaymentOptionEmailConstraint) {
			throw new UnexpectedTypeException($constraint, UniqueCustomerPaymentOptionEmailConstraint::class);
		}

		$value = '';
		$field = '';

		if (PaymentOptionService::isConfiguredToUseEmail($transactionModel->getPaymentOptionCode())) {
			$value = $transactionModel->getEmail() ?? '';
			if ($value === '') return;
			$field = 'email';
		}

		if (PaymentOptionService::isConfiguredToUseAccountId($transactionModel->getPaymentOptionCode())) {
			$value = $transactionModel->getAccountId() ?? '';
			if ($value === '') return;
			$field = PaymentOptionService::USDT ? 'email' : 'account_id';
		}

		$availability = $this->cpoService->checkAvailability(
			$transactionModel->getCustomer()->getId(),
			$transactionModel->getPaymentOptionCode(),
			$field,
			$value
		);

		if (!$availability['available']) {
			if ($field == 'account_id') {
				$this->context->buildViolation($constraint->getAccountIdViolationMessage())->addViolation();
				return;
			} else {
				if ($transactionModel->getPaymentOptionCode() == PaymentOptionService::USDT) {
					$this->context->buildViolation($constraint->getAccountIdUsdtViolationMessage())->addViolation();
				} else {
					$this->context->buildViolation($constraint->getEmailViolationMessage())->addViolation();
				}

				return;
			}
		}
	}
}
