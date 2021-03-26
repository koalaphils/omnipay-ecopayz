<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class MaxActiveReferralNameConstraint extends Constraint
{
    public $message = "There are already {{ max }} that was active. Suspend another referral name to resume.";
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