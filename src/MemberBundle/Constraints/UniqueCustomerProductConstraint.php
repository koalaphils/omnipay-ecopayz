<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueCustomerProductConstraint extends Constraint
{
    protected $errorPath = null;
    protected $action = null;
    protected $message = null;
    protected $withError = "yes";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getErrorPath()
    {
        return $this->errorPath;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getWithError()
    {
        return $this->withError;
    }
}
