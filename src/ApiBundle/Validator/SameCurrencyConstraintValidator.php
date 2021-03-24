<?php

namespace ApiBundle\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use DbBundle\Entity\User;

class SameCurrencyConstraintValidator extends ConstraintValidator
{
    private $sourceCustomer;

    public function __construct(User $user)
    {
        $this->sourceCustomer = $user->getCustomer();
    }

    public function validate($subTransactions, Constraint $constraint)
    {      
        if ($subTransactions instanceof ArrayCollection) {
            $hasDifferent = false;
            foreach ($subTransactions as $key => $subTransaction) {
                $recipientCustomer = $subTransaction->getProduct()->getCustomer();
                if ($this->sourceCustomer->getCurrency() !== $recipientCustomer->getCurrency()) {
                    $this->context->buildViolation($constraint->message)->addViolation();
                }
            }
        }
    }
}
