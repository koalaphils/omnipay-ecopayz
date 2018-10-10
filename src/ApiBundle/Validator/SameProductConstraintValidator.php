<?php

namespace ApiBundle\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * This constraint is used on P2P Transaction.
 *
 * This contraint will add a validation error if 
 * the user tries to transfer on same product. 
 * Same product means if the form contains two or more subtransactions
 * that has the same product username and code.
 */
class SameProductConstraintValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManager $em) 
    {
        $this->em = $em;
    }

    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof ArrayCollection) {
            $hasDuplicate = false;
            foreach ($value as $key => $subTransaction) {
                $customerProduct = $subTransaction->getProduct();
                foreach ($value as $key2 => $subTransaction2) {
                    if ($key !== $key2) {
                        if ($customerProduct === $subTransaction2->getProduct()) {
                            $hasDuplicate = true;
                            break;
                        }
                    }
                }  
                if ($hasDuplicate) {
                    $this->context->buildViolation($constraint->message)
                        ->addViolation();
                    break;
                }
            }
        }
    }
}
