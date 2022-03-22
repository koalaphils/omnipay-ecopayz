<?php

namespace ApiBundle\Validator;

use DbBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use ApiBundle\Request\Transaction\WithdrawRequest;

class VerifiedMemberAccountConstraintValidator extends ConstraintValidator
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage) 
    {
        $this->tokenStorage = $tokenStorage;
    }

	/**
	 * @param WithdrawRequest $transactionModel
	 * @param Constraint $constraint
	 */
	public function validate($transactionModel, Constraint $constraint)
	{
        $member = $this->getUser()->getMember();

        if ($member == null) {
            return;            
        }

        if (!$member->isVerified()) {
            $this->context->buildViolation($constraint->getAccountValidationMessage())
            ->addViolation();
        }
    }

    private function getUser(): User
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}