<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TransactionPasswordConstraintValidator extends ConstraintValidator
{
    private $user;
    private $encoderFactory;

    public function __construct($user, $encoderFactory) 
    {
        $this->user = $user;
        $this->encoderFactory = $encoderFactory;
    }

    public function validate($value, Constraint $constraint)
    {   
        $encoder = $this->encoderFactory->getEncoder($this->user);

        if (!$encoder->isPasswordValid($this->user->getCustomer()->getTransactionPassword(), $value, '')) {
            $this->context->buildViolation($constraint->message)
            ->addViolation();
        }
    }
}
