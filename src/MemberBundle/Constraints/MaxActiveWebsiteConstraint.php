<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class MaxActiveWebsiteConstraint extends Constraint
{
    public $message = "There are already {{ max }} that was active. Suspend the one website to add another one.";
    public $memberIdPath;
    public $errorPath;

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getMemberIdPath(): string
    {
        return $this->memberIdPath;
    }

    public function getMessage(): string
    {
        return (string) $this->message;
    }

    public function getErrorPath(): string
    {
        return (string) $this->errorPath;
    }
}
