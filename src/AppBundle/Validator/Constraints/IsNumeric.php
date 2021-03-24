<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description of IsNumeric.
 *
 * @author cnonog
 */
class IsNumeric extends Constraint
{
    protected $message = 'The value must be numeric';

    public function getMessage()
    {
        return $this->message;
    }
}
